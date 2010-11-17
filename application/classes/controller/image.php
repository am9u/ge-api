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

        $image = new Model_Image($id);

        $image->load('{_id:"'.$id.'"}');

        $this->_payload = $image;

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );

        Kohana::$log->add('Controller_Image->action_index()', 'photo name='.$image->name.' url='.$image->url);
    }

    /**
     *
     */
    public function action_create()
    {


        $array = Validate::factory($_FILES);
        $array->rule('photo', 'Upload::not_empty');
        $array->rule('photo', 'Upload::type', array(array('jpg', 'jpeg', 'png', 'gif')));

        // consistent with php.ini setting. will have to change there if support for large files is needed
        $array->rule('photo', 'Upload::size', array('2M')); 

        if($array->check())
        {
            $file = $_FILES['photo'];

            Upload::$remove_spaces = TRUE;

            $hashed_name = hash('sha1', uniqid().$file['name']).'.'.Controller_Image::file_extension($file['name']);
            $path = Upload::save($file, $hashed_name, $_SERVER['DOCUMENT_ROOT'].'/images'); // returns absolute path on filesystem
            $relpath = '/images/'.$hashed_name;

            // process image... move this to separate function
            // $image = Image::factory($path);
            // $image->crop(200, 200, 0, 0);
            // $image->save('uploads/'.$_FILES['photo']['name'].'_200x200.jpg');

            // todo: upload to aws.s3

            // mongodb document
            $image = new Model_Image();

            $image->name = $file['name'];
            $image->url  = $relpath;

            $image->save();

            $this->_status = array(
                'type'    => 'success',
                'code'    => '200',
                'message' => 'OK'
            );

            $this->_payload = $image;
        } 
        else 
        {
            $errors = $array->errors('image');

            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
                'message' => $errors['photo']
            );
            
        }
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

    private status function file_extension($filename)
    {
        var idx = strripos($filename, '.');
        if(idx === FALSE)
        {
            return FALSE;
        }
        else
        {
            return substr($filename, idx+1);
        }
    }
}
