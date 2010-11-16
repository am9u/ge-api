<?php defined('SYSPATH') or die('No direct script access.'); 
class Controller_Image extends Controller_REST 
{
    protected $_model_type = 'image';

    // set default directory
    // Upload::$default_directory = 'uploads';

    /**
     *
     */
    public function action_index($id=NULL)
    {
        // $image = ORM::factory('image', 1);
        // $out   = $image->name.'<br/>';

        // $image_url = $image->imageurls->find(); // $image_urls = ->find_all()
        // $profile = $image_url->profile; // profile is collection of metadata about image type: dimensions, quality, etc.

        // $out .= $image_url->url.'<br/>';
        // $out .= $profile->name.'<br/>';

        // $this->request->response = 'photo index:<br/>'.$out;

        //$image = n
    }

    /**
     *
     */
    public function action_create()
    {

        $array = Validate::factory($_FILES);
        $array->rule('photo', 'Upload::not_empty');
        $array->rule('photo', 'Upload::type', array(array('jpg', 'jpeg', 'png', 'gif')));

        if($array->check())
        {
            $file = $_FILES['photo'];
            //$image = ORM::factory('image');


            // save local copy
            $path = Upload::save($file, NULL, 'uploads'); // returns absolute path on filesystem
            // $out = 'file saved<br/>';

            // process image... move this to separate function
            // $image = Image::factory($path);
            // $image->crop(200, 200, 0, 0);
            // $image->save('uploads/'.$_FILES['photo']['name'].'_200x200.jpg');

            // todo: upload to aws.s3

            // mongodb document
            $image = new Model_Image();

            $image->name = $file['name'];
            $image->url  = $path;

            $image->save();

            $this->_status = array(
                'type'    => 'success',
                'code'    => '200',
                'message' => 'OK. Image id='.$image->id.' uploaed to '.$path
            );
        } 
        else 
        {
            // @TODO: throw error status code in HTTP response

            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
                'message' => 'Error creating image'
            );
            
        }


        // $this->request->response = $out; 
    }

    // public function after() {}

    private static function _upload_to_s3($file_path)
    {
        $s3 = new Amazon_S3();
        $response = $s3->upload($file_path, 'txt/test.txt', 'ferrinho');

        if($response)
        {
            $out = 'Successfully uploaded file to AWS S3';
        }
        else 
        {
            $out = 'Upload to AWS S3 failed!';
        }
    }
}
