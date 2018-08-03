<?php

require 'OoData.php';
require 'Models/Post.php';
require 'Models/Notebook.php';

$post = new Post;
$post->refresh(2);
echo '<pre>';
var_dump($post);
echo '</pre>';



