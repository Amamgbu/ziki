<?php

session_start();
require_once 'config.php';
require_once 'functions/Post.php';

if (!isset($_SESSION['loggedUserId'])) {
    $response = array("error" => true, "message" => "you are not authorized in here");
    die(json_encode($response));
}

$data = file_get_contents("posts.json");
$posts = json_decode($data, true);
$getAllPosts = Post::fetchAllPosts($posts);
var_dump($getAllPosts);
$posts = array();
if (!empty($getAllPosts)) {
    foreach ($getAllPosts as $blog) {
        $postId = $blog->getId();
        $postTitle = $blog->getStoryTitle();
        $postBody = $blog->getStoryBody();
        $postPic = $blog->getStoryImage();
        $postTimestamp = $blog->getTimePosted();
        $post['id'] = $postId;
        $post['title'] = $postTitle;
        $post['body'] = $postBody;
        $post['post_image'] = $postPic;
        $post['post_timestamp'] = $postTimestamp;
        array_push($posts, $post);
    }
    $result['error'] = false;
    $result['result'] = $posts;
}
else{
    $result['error'] = true;
    $result['message'] = 'internal server error';
}
echo(json_encode($result));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $body = isset($_POST['body']) ? trim($_POST['body']) : null;
    $file = $_FILES['image'];
    $user = $_SESSION['loggedUserId'];
    $new_file_name = date('dmYHis').str_replace(" ", "", basename($_FILES['image']['name']));
    $image_type = strtolower(pathinfo($new_file_name, PATHINFO_EXTENSION));
    if ($image_type != "jpg" && $image_type != "png" && $image_type != "jpeg") {
        $response['error'] = true;
        $response['message'] = 'Please make sure you uploading your image';
    }
    else{
        $target_file = SITE_ROOT.'/uploads/'.$new_file_name;
        if ($file['error'] === 0) {
            $upload = move_uploaded_file($file['tmp_name'], $target_file);
            if ($upload) {
                $newPost = new Post();
                $newPost->setUserId($user);
                $newPost->setStoryBody($body);
                $newPost->setStoryTitle($title);
                $newPost->setStoryImage($target_file);
                if ($newPost->savePost()) {
                    $response = array('error' => false, 'message' => 'post published successfully');
                } else {
                    $response = array('error' => true, 'message' => 'error occured while posting');
                }
            }
            else{
                $response['error'] = true;
                $response['message'] = 'Error while uploading image';
            }
        }
        else{
            $response['error'] = true;
            $response['message'] = 'Error, please select an image';
        }
    }
    die(json_encode($response));
}
