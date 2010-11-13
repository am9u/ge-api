<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Test extends Controller
{
    /**
     * Creates test tags 
     */
    // public function action_create_tags()
    // {
    //     // event tag
    //     $tag_data = array(
    //         'name' => 'Event'
    //     );

    //     $parent_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();

    //     $tag_data = array(
    //         'name' => 'Fatty Crab',
    //         'parent_id' => $parent_tag->id
    //     );

    //     $child_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();

    //     $tag_data = array(
    //         'name' => 'A Fan Ti',
    //         'parent_id' => $parent_tag->id
    //     );

    //     $child_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();

    //     // club tag
    //     $tag_data = array(
    //         'name' => 'Club'
    //     );

    //     $parent_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();

    //     $tag_data = array(
    //         'name' => 'New York',
    //         'parent_id' => $parent_tag->id
    //     );

    //     $child_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();

    //     $tag_data = array(
    //         'name' => 'Los Angeles',
    //         'parent_id' => $parent_tag->id
    //     );

    //     $child_tag = ORM::factory('tag')
    //                     ->values($tag_data)
    //                     ->save();
    //     
    //      $tag_data = array(
    //          'name' => 'Food'
    //      );

    //      $gparent_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    //      $tag_data = array(
    //          'name' => 'Ingredient',
    //          'parent_id' => $gparent_tag->id
    //      );

    //      $parent_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    //      $tag_data = array(
    //          'name' => 'Cheese',
    //          'parent_id' => $parent_tag->id
    //      );

    //      $child_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    //      $tag_data = array(
    //          'name' => 'Tripe',
    //          'parent_id' => $parent_tag->id
    //      );

    //      $child_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();
    //      
    //  
    //      $tag_data = array(
    //          'name' => 'Cuisine',
    //          'parent_id' => $gparent_tag->id
    //      );

    //      $parent_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    //      $tag_data = array(
    //          'name' => 'Peruvian',
    //          'parent_id' => $parent_tag->id
    //      );

    //      $child_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    //      $tag_data = array(
    //          'name' => 'Japanese',
    //          'parent_id' => $parent_tag->id
    //      );

    //      $child_tag = ORM::factory('tag')
    //                      ->values($tag_data)
    //                      ->save();

    // }

    public function action_list_tags()
    {
        $out = '<ul>';

        $parent_tags = ORM::factory('tag')->core_tags()->find_all();

        foreach($parent_tags as $parent_tag)
        {
            $out .= '<li>'.$parent_tag->name;

            if($parent_tag->children->count_all() > 0)
            {
                $out .= '<ul>';
                foreach($parent_tag->children->find_all() as $child_tag)
                {
                    $out .= '<li>'.$child_tag->name;
                    
                    if($child_tag->children->count_all() > 0)
                    {
                        $out .= '<ul>';
                        foreach($child_tag->children->find_all() as $gchild_tag)
                        {
                            $out .= '<li>'.$gchild_tag->name.'</li>';
                        }
                        $out .= '</ul>';
                    }
                    $out .= '</li>';
                }
                $out .= '</ul>';
            }
            
            $out .= '</li>';    
        }

        $out .= '</ul>';

        $this->request->response = $out;
    }

    public function action_list_descendents()
    {
        $event_parent_tag = ORM::factory('tag')->core_tag('Event');
        $out = $event_parent_tag->count_all().'<br/>';

        $events = $event_parent_tag->find()->descendents();
        $out .= $event_parent_tag->find()->descendents()->count_all().'<br/>';

        $out .= '<ul>';
        foreach($event_parent_tag->find()->descendents()->find_all() as $event_tag)
        {
            $out .= '<li>'.$event_tag->name.' => '.$event_tag->events->find()->name.'</li>';
        }
        $out .= '</ul>';
        $this->request->response = $out;
        

    }

    public function action_test_orm_cache()
    {
        $model = ORM::factory('event', 1)->find();
        Cache::instance('memcache')->set('event_1', $model, 300);
        $this->request->response = 'Cache is set!<br/>Storing event with name='.$model->name;
    }

    public function action_check_orm_cache()
    {
        $model = Cache::instance('memcache')->get('event_1');
        $out = 'Cache retrieved<br/> Event name is:'.$model->name.'<br/>';
        $out .= 'Event venue is:'.$model->venue->find()->name;
        $this->request->response = $out;
    }

    public function action_find_file()
    {
        $path = Kohana::find_file('vendor', 'corm');
        Kohana::$log->add('find file:', $path);
        // print_r(Kohana::$_paths);
        $this->request->response = 'path: '.$path;
    }
}

