<?php

namespace App\Http\Controllers;

use getID3;
use App\Models\Video;
use App\Traits\ShortTraits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreVideoRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoController extends Controller
{
    use ShortTraits;
    public function store(StoreVideoRequest $request)
    {
        $video_file = $request->file('video');

        $new_name = 'vid_' . time() . '.' . $video_file->getClientOriginalExtension();
        $path = $video_file->storeAs('videos',   $new_name);

        $localPath = $video_file->storeAs('videos',   $new_name, 'public');

        if ($path){
            $byte = $video_file->getSize() / (1024 * 1024);
            $size = round($byte, 2) . "mb";
            $getID3 = new getID3();
            $file_path = $this->storePath('s3', $path);

            $local_path = $this->storePath('local', 'public/' . $localPath);
            $full_path = 'https://hng-video-upload.s3.us-east-1.amazonaws.com/' . $file_path;
            $file_info = $getID3->analyze($local_path);
            $video_length = isset($file_info['playtime_string']) ? $file_info['playtime_string'] : '00.00';
            // $this->transcribe($full_path);
            $save = $this->saveVideo([
                $new_name,
                $size,
                $video_length,
                $full_path,
                Carbon::now()
            ]);
            // Deleting the file from local storage
            $filePathToDelete = 'app/public/videos/' .   $new_name;
            $fullFilePath = storage_path($filePathToDelete);
            unlink($fullFilePath);

            if ($save) {
                $data = [
                    'video_name' =>   $new_name,
                    'video_size' => $size,
                    'video_length' => $video_length,
                    'video_path' => $full_path
                ];

                return  $this->success('Video has been uploaded successfully', 200, $data);
            } else{
                return $this->error('Bad Request an Error Occurred', 422);
            }
        }
        return $this->error('Error saving video', 422);
    }


    public function index()
    {
        $video = Video::all(['id', 'name', 'size', 'length', 'path', 'uploaded_time']);
        if (!$video->isEmpty()) :
            return $this->fetchOrFailData(200, 'success', JsonResource::collection($video));
        else :
            return $this->fetchOrFailData(404, 'error', 'Video not found');
        endif;
    }

    public function show($id)
    {
        $video = Video::where('id',$id)->first();
        return $this->success('Success!', 200,$video);
    }

    public function transcribe($id)
    {
        $get = Video::where('id', $id)->value('path');

         if( $this->transcribe($get)){
            return $this->fetchOrFailData(200, 'success', $get);
         }
         else{
            return $this->fetchOrFailData(422, 'error', 'An error occurred');
        }
    }

    public function destroy($id)
    {
        DB::table('videos')->where('id', $id)->delete();

        return $this->success("Video deleted successfully", 200, []);
    }
}
