<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatSendRequest;
use App\Http\Requests\ChatStoreRequest;
use App\Http\Requests\ChatUpdateRequest;
use App\Http\utils;
use App\Models\Chat;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    private $levels = [
        '1-4' => '1-4 классе',
        '5-9' => '5-9 классе',
        '10-11' => '10-11 классе',
        'student' => 'ВУЗе',
        'self' => 'саморазвитии'
    ];
    public function store (ChatStoreRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $validated = $request->validated();

        $validated["user_id"] = $user->id;
        $validated["subjects"] = json_encode($validated["subjects"]);

        $dialog = [];
        $dialog[] = [
            "role" => "system",
            "content" => "Ты чат-бот, который помогает по школьной программе ТОЛЬКО по этим предметам (никаких других): "
                . implode(", ", Subject::whereIn("id", json_decode($validated["subjects"]))->pluck("name")->toArray())
                . ', при этом при любом другом вопросе веди тему в нужное русло (к школьным предметам), не отвечая на них.'
                . ' Не используй ни в каком случае никакую (!) Markdown-разметку. Только текст и обязательно используй LaTeX выражения для формул (!!!обязательно оборачивая в $ или $$) (в сообщениях меньше 1000 символов)!'
                . ' Не используй никакие ссылки'
                . ' Учитывай, что пользователь находится в ' . $this->levels[$user->level]
                . (($user->faculty != null && $user->faculty != '') ? ('(Факультет: ' . $user->faculty . ')') : '')
                . ' и помогай ему с этим. Если ответ очень большой (больше 1000 символов!), то используй формат HTML (не забудь указать charset utf8 и font-family: DejaVu Sans) (для конвертации в PDF) разметку для наиболее красивого ответа (!обязательно использование {type:PDF} в начале ответа!! СТРОГО ЗАПРЕЩЕНО использoвать любые LaTeX выражения (никаких $ или $$ вообще). Все формулы и обозначения пиши только обычным текстом или в виде HTML-мнемоник (например, &sup2; для квадратов и т.д.))'
        ];
//        $dialog[] = [
//            "role" => "assistant",
//            "content" => "Привет, я чат-бот, который помогает по школьной программе. Чем могу помочь?"
//        ];

        $validated["dialog"] = json_encode($dialog);
        $chat = Chat::create($validated);

        return response()->json($chat);
    }

    public function delete (Chat $chat, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($chat->user_id !== $user->id) abort(403, "You can't delete this chat");

        $chat->delete();
        return response()->json();
    }

    public function update (Chat $chat, ChatUpdateRequest $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        $validated = $request->validated();

        if ($request->has('subjects')) $validated["subjects"] = json_encode($validated["subjects"]);
        else $validated["subjects"] = $chat->subjects;

        $dialog = json_decode($chat->dialog, true);

        if (!$validated["level"]) $validated["level"] = $chat->level;
        if (!$validated["faculty"]) $validated["faculty"] = $chat->faculty;

        foreach ($dialog as &$item) { // <--- ВАЖНО: амперсанд!
            if (isset($item['role']) && $item['role'] === 'system') {
                $item['content'] = "Ты чат-бот, который помогает по школьной программе ТОЛЬКО по этим предметам (никаких других): "
                    . implode(", ", Subject::whereIn("id", json_decode($validated["subjects"]))->pluck("name")->toArray())
                    . ', при этом при любом другом вопросе веди тему в нужное русло (к школьным предметам), не отвечая на них.'
                    . ' Не используй ни в каком случае никакую (!) Markdown-разметку. Только текст и обязательно используй LaTeX выражения для формул (!!!обязательно оборачивая в $ или $$) (в сообщениях меньше 1000 символов)!'
                    . ' Не используй никакие ссылки.'
                    . ' Учитывай, что пользователь находится в ' . $this->levels[$validated["level"]]
                    . (($validated["faculty"] != null && $validated["faculty"] != '') ? ('(Факультет: ' . $validated["faculty"] . ')') : '')
                    . ' и помогай ему с этим. Если ответ очень большой (больше 1000 символов!), то используй формат HTML (не забудь указать charset utf8 и font-family: DejaVu Sans) (для конвертации в PDF) разметку для наиболее красивого ответа (!обязательно использование {type:PDF} в начале ответа!! СТРОГО ЗАПРЕЩЕНО использoвать любые LaTeX выражения (никаких $ или $$ вообще). Все формулы и обозначения пиши только обычным текстом или в виде HTML-мнемоник (например, &sup2; для квадратов и т.д.))';
                break;
            }
        }
        unset($item);

        $validated["dialog"] = json_encode($dialog);
        $chat->update($validated);

        return response()->json($chat);
    }

    public function audio (Chat $chat, Request $request) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($chat->user_id !== $user->id) abort(403, "You can't send message to this chat");
        if ($user->tokens <= 0 && $user->is_sub == 0) abort(403, "You don't have tokens");

        $audioFile = $request->file('audio');
        $extension = utils::getExtension($audioFile);

        $time = time();
        $path = "audio/" . $chat->id . "_" . $time . "." . $extension;
        Storage::disk('public')->putFileAs('audio', $audioFile, $chat->id . "_" . $time . "." . $extension);

        $fullPath = Storage::disk('public')->path($path);

        if ($audioFile && $audioFile->isValid()) {
            $response = Http::withToken(env('OPENAI_TOKEN'))
                ->attach(
                    'file',
                    fopen($fullPath, 'r'),
                    basename($fullPath)
                )
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    [
                        'name' => 'model',
                        'contents' => 'gpt-4o-transcribe',
                    ],
                ]);

            $text = $response->json('text');
            $request["content"] = $text;

            return $this->send($chat, ChatSendRequest::createFrom($request), $path);
        } else abort (400, "Invalid audio file");
    }

    public function send (Chat $chat, ChatSendRequest $request, $audio = null) {
        $user = User::where("telegram_id", $request["initData"]["user"]["id"])->firstOrFail();
        if ($chat->user_id !== $user->id) abort(403, "You can't send message to this chat");
        if ($user->tokens <= 0 && $user->is_sub == 0) abort(403, "You don't have tokens");

        $dialog = json_decode($chat->dialog, true);
        if ($request->has("image")) {
            $image = $request->file("image");

            $time = time();
            $fileName = "image_$time." . $image->extension();
            $file = Storage::disk("public")->putFileAs("chat", $image, "image_$time" . "." . $image->extension());

            $filePath = Storage::disk("public")->path("chat/" . $fileName);
            $response = Http::attach(
                'source',
                file_get_contents($filePath),
                $fileName
            )->post('https://freeimage.host/api/1/upload?key=6d207e02198a847aa98d0a2a901485a5', [
                'type' => 'file'
            ]);
            Log::critical($response->json());
            $publicUrl = $response->json()['image']['url'];

            $dialog[] = ["role" => "user", "content" => [
                    [
                        "type" => "text",
                        "text" => $request["content"]
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => [
//                            "url" => Storage::disk("public")->url($file)
                            "url" => $publicUrl,
                            "detail" => "high"
                        ]
                    ]
                ]
            ];
        } else $dialog[] = ["role" => "user", "content" => $request["content"], "audio" => $audio];
        $chat->update(["dialog" => json_encode($dialog)]);

        $payload = [
            "model" => "gpt-4.1",
            "messages" => $dialog,
            "stream" => true,
        ];

        if ($user->is_sub == 0) $user->tokens--;
        $user->save();

        $client = new \GuzzleHttp\Client();
        return response()->stream(function () use ($user, $client, $payload, &$chat, &$dialog) {
            try {
                $response = $client->post('https://api.openai.com/v1/chat/completions', options: [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('OPENAI_TOKEN'),
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'text/event-stream',
                    ],
                    'body'    => json_encode($payload),
                    'stream'  => true,
                    'timeout' => 0
                ]);

                $body = $response->getBody();
                $buffer = '';
                $assistant = "";

                while (!$body->eof()) {
                    $chunk = $body->read(8192);
                    if ($chunk === false || $chunk === '') {
                        continue;
                    }
                    $buffer .= $chunk;

                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = substr($buffer, 0, $pos);
                        $buffer = substr($buffer, $pos + 1);
                        $line = trim($line);

                        if (str_starts_with($line, 'data:')) {
                            $data = trim(substr($line, 5));
                            if ($data === '[DONE]') {
                                $newDialog = ["role" => "assistant", "content" => $assistant];
                                if (str_contains($assistant, '{type:PDF}')) {
                                    Storage::disk('public')->makeDirectory('chat');

                                    $time = time();
                                    $url = "chat/response_$time" . ".pdf";

                                    $html = str_replace('{type:PDF}', '', $assistant);
                                    $pdf = Pdf::loadHTML($html);
                                    $pdf->setOptions(['defaultFont' => 'DejaVu Sans']);

                                    Storage::disk("public")->put($url, $pdf->output());
                                    $newDialog["file"] = $url;
                                    echo '{file_url: "' . $url . '"}';
                                }

                                $dialog[] = $newDialog;
                                $chat->update(["dialog" => json_encode($dialog)]);
                                return;
                            }
                            if (!empty($data)) {
                                $json = json_decode($data, true);
                                if (
                                    isset($json['choices'][0]['delta']['content']) &&
                                    $json['choices'][0]['delta']['content'] !== null
                                ) {
                                    echo $json['choices'][0]['delta']['content'];
                                    $assistant .= $json['choices'][0]['delta']['content'];

                                    flush();
                                }
                            }
                        }
                    }
                }
                // обрабатываем, если вдруг в буфере осталась строка без \n в конце
                if (trim($buffer) !== '') {
                    $line = trim($buffer);
                    if (str_starts_with($line, 'data:')) {
                        $data = trim(substr($line, 5));
                        if ($data !== '[DONE]' && !empty($data)) {
                            $json = json_decode($data, true);
                            if (
                                isset($json['choices'][0]['delta']['content']) &&
                                $json['choices'][0]['delta']['content'] !== null
                            ) {
                                echo $json['choices'][0]['delta']['content'];
                                $assistant .= $json['choices'][0]['delta']['content'];

                                flush();
                            }
                        }
                    }
                }

                $newDialog = ["role" => "assistant", "content" => $assistant];
                if (str_contains($assistant, '{type:PDF}')) {
                    Storage::disk('public')->makeDirectory('chat');

                    $time = time();
                    $url = "chat/response_$time" . ".pdf";

                    $html = str_replace('{type:PDF}', '', $assistant);
                    $pdf = Pdf::loadHTML($html);
                    $pdf->setOptions(['defaultFont' => 'DejaVu Sans']);

                    Storage::disk("public")->put($url, $pdf->output());
                    $newDialog["file"] = $url;
                    echo '{file_url: "' . $url . '"}';
                }

                $dialog[] = $newDialog;
                $chat->update(["dialog" => json_encode($dialog)]);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : '';
                Log::error('Ошибка стрима: ' . $e->getMessage() . ' Код: ' . $statusCode . ' Ответ: ' . $responseBody);

                echo "[Stream error]: " . $e->getMessage() . "\n";
                if($statusCode) echo "HTTP статус: " . $statusCode . "\n";
                if($responseBody) echo "Тело ответа: " . $responseBody . "\n";

                flush();

                $user->tokens++;
                $user->save();
            } catch (\Exception $e) {
                Log::error('Ошибка стрима (общее исключение): ' . $e->getMessage());
                echo "[Stream error]: " . $e->getMessage() . "\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    }

}
