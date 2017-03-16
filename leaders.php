<?php
  /**
   * Leader
   *
   * @package Membao
   * @author Alan Kawamara
   * @copyright 2017
   */
  define("_VALID_PHP", true);
  require_once("init.php");
  
  if (isset($_GET['leadername'])):
    $leaderrow = $leader->renderLeader();    
    if(!$leaderrow):
      redirect_to(SITEURL . '/404.php');
    endif;
  else:
    $allleaders = $leader->getLeaders();
  endif;

?>
<?php require_once (THEMEDIR . "/leaders.tpl.php");?>