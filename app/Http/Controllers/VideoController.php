<?php

namespace App\Http\Controllers;

use getID3;
use App\Models\Video;
use App\Traits\ShortTraits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\VideoResource;
use App\Http\Requests\StoreVideoRequest;

class VideoController extends Controller
{
    use ShortTraits;

    protected $video;

    public function store(StoreVideoRequest $request)
    {

        $uploadedVideo = $request->file('video');

        $timestampName = 'untitled_' . time() . '.' . $uploadedVideo->getClientOriginalExtension();

        $path = $uploadedVideo->storeAs('videos',   $timestampName);
        $localPath = $uploadedVideo->storeAs('videos',   $timestampName, 'public');

        if ($path){
            $videoInByte = $uploadedVideo->getSize() / (1024 * 1024);
            $videoSize = round($videoInByte, 2) . "mb";
            $getID3 = new getID3();
            $videoFilePath = $this->storePath('s3', $path);
            $localVideoFilePath = $this->storePath('local', 'public/' . $localPath);
            $fullVideoPath = 'https://hng-video-upload.s3.us-east-1.amazonaws.com/' . $videoFilePath;
            $fileInfo = $getID3->analyze($localVideoFilePath);
            $videoLength = isset($fileInfo['playtime_string']) ? $fileInfo['playtime_string'] : '00.00';
            // $this->transcribe($fullVideoPath);
            $save = $this->saveVideo([
                $timestampName,
                $videoSize,
                $videoLength,
                $fullVideoPath,
                Carbon::now()
            ]);
            // Deleting the file from local storage
            $filePathToDelete = 'app/public/videos/' .   $timestampName;
            $fullFilePath = storage_path($filePathToDelete);
            unlink($fullFilePath);

            if ($save) {
                return  $this->success([$timestampName, $videoSize, $videoLength, $fullVideoPath], 200);
            } else{
                return $this->error('Bad Request an Error Occurred', 422);
            }
        }
        return $this->error('An Error occurred While trying to Save file, Check Internet Connection ', 422);
    }


    public function index()
    {
        $video = Video::all(['id', 'name', 'size', 'length', 'path', 'uploaded_time']);
        if (!$video->isEmpty()) :
            return $this->fetchOrFailData(200, 'success', VideoResource::collection($video));
        else :
            return $this->fetchOrFailData(404, 'error', 'no data found');
        endif;
    }

    public function transcribe(int $id)
    {
        $get = Video::where('id', $id)->select('path')->get();

         if( $this->transcribe($get)):
        return $this->fetchOrFailData(200, 'success', $get);
         else:
            return $this->fetchOrFailData(422, 'error', 'An error occurred');
         endif;
    }
    public function destroy($id)
    {
        DB::table('videos')->where('id', $id)->truncate();

        return$this->success("Video deleted successfully", 200);
    }
}
