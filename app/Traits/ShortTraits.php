<?php
    namespace App\Traits;

    use App\Models\Video;

    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Storage;

    trait ShortTraits
    {
        public function storePath($storage, $link)
        {
            return Storage::disk($storage)->path($link);
        }
        public function transcribe($link)
        {
            $url = fopen($link, 'r');
            $response = Http::withHeaders(['Authorization' => 'Bearer GR1EJSH7RYBZGTJLU8DL92IXTK191K1X'])
                ->attach('file', $url)
                ->post(
                    'https://transcribe.whisperapi.com',
                    [
                        'fileType' => 'mp4',
                        'diarisation' => 'false',
                        'task' => 'transcribe'
                    ]
                );
            return $response->json();
        }
        public function success($array = [], int $statusCode)
        {
            return response()->json([
                'StatusCode' => 201,
                'message' => 'Image has been uploaded successfully',
                'status' => 'Created',
                'data' => [
                    'video_name' =>   $array[0],
                    'video_size' => $array[1],
                    'video_length' => $array[2],
                    'video_path' => $array[3]
                ]
            ], $statusCode);
        }
        public function error($message, $statusCode)
        {
            return response()->json([
                'StatusCode' => $statusCode,
                'status' => 'error',
                'message' => $message,
            ]);
        }
        public function fetchOrFailData($statusCode, $status, $video = [])
        {
            return response()->json([
                'StatusCode' => $statusCode,
                'status' => $status,
                'data' => $video
            ], $statusCode);
        }
        public function saveVideo($array = [])
        {
            $video = new Video;
            $video->name =   $array[0];
            $video->size = $array[1];
            $video->length = $array[2];
            $video->path = $array[3];
            $video->uploaded_time = $array[4];
            $checking = $video->save();
            if (!$checking) {
                return false;
            }
            return true;
        }

    }