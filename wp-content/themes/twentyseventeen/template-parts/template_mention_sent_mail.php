<?php
/**
 * Template Name: Mention Sent Mail
 */

get_header();
?>
<div class="main">
    <form method="get">
        user   <input type="text" name="user">
        content<input type="text" name="content">
        <input type="submit" name="submit">
    </form>
</div>
<?php

$content    = $_GET['content'];
$user_input = substr($_GET['user'], 1);
$user       = get_user_by('login', $user_input);

$subject = 'The subject';
$headers = array('Content-Type: text/html; charset=UTF-8');
$email = $user->data->user_email;
if ($_GET['submit']) {
    if ($user) {
        wp_mail($email, $subject, $content, $headers);
    } else {
        echo "user was not exist";
    }
}
get_footer(); ?>