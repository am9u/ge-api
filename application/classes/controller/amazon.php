<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Amazon extends Controller
{
    public function action_s3()
    {
        $s3 = new Amazon_S3();
        $response = $s3->upload('uploads/test.txt', 'txt/test.txt', 'ferrinho');

        if($response)
        {
            $out = 'Successfully uploaded file to AWS S3';
        }
        else 
        {
            $out = 'Upload to AWS S3 failed!';
        }

        $this->request->response = $out;
    }

}
