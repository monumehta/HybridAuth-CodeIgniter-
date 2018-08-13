<?php
if(!empty($error_msg)){
    echo '<p class="error">'.$error_msg.'</p>';    
}

if(!empty($userData)){ ?>
    <div class="login-form">
        <div class="head">
            <img src="<?php echo $userData['picture_url']; ?>" alt=""/>
        </div>
        <div class="content">
        <li>
            <p><?php echo $userData['first_name'].' '.$userData['last_name']; ?></p>
        </li>
        <li>
            <p><?php echo $userData['email']; ?></p>
        </li>
        <li>
            <p><?php echo $userData['locale']; ?></p>
        </li>
        <div class="foot">
            <a href="<?php echo base_url().'user_linkedin/logout'; ?>">Logout</a>
            <a href="<?php echo $userData['profile_url']; ?>" target="_blank">View Profile</a>
            <div class="clear"> </div>
        </div>
        </div>
    </div>
<?php
}else{
    echo '<div class="linkedin_btn"><a href="'.$oauthURL.'"><img src="'.base_url().'linkedin.png" /></a></div>';
}
?>