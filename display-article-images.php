<?php
function displayArticle($title, $thumbnail, $articleUrl, $className = "") {
?>
    <a href="<?php echo $articleUrl; ?>" target="_blank">
        <div class="asideArticleTitle"><?php echo $title; ?></div>
        <img class="asideArticleImage" src="<?php echo $thumbnail; ?>" alt="<?php echo $title; ?>"/>
    </a>
<?php    
}