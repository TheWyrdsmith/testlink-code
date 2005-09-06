<?php
/** 
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * This script is distributed under the GNU General Public License 2 or later. 
 *  
 * @filesource $RCSfile: reqSpecView.php,v $
 * @version $Revision: 1.7 $
 * @modified $Date: 2005/09/06 06:46:13 $ by $Author: franciscom $
 * @author Martin Havlat
 * 
 * Screen to view existing requirements within a req. specification.
 * 
 * @author Francisco Mancardi - fm - fckeditor
 * 20050930 - MHT - Database schema changed (author, modifier, status, etc.)
 *
 */
////////////////////////////////////////////////////////////////////////////////
require_once("../../config.inc.php");
require_once("common.php");
require_once("users.inc.php");
require_once('requirements.inc.php');
require_once("../../third_party/fckeditor/fckeditor.php");


// init page 
tLog('POST: ' . implode(',',$_POST));
testlinkInitPage();

$sqlResult = null;
$action = null;
$sqlItem = 'Requirement';

$arrReq = array();
$bGetReqs = TRUE; // collect requirements as default

$template = 'reqSpecView.tpl'; // main template

$idSRS = isset($_GET['idSRS']) ? strings_stripSlashes($_GET['idSRS']) : null;
$idReq = isset($_POST['idReq']) ? strings_stripSlashes($_POST['idReq']) : null;
$title = isset($_POST['title']) ? strings_stripSlashes($_POST['title']) : null;
$scope = isset($_POST['scope']) ? strings_stripSlashes($_POST['scope']) : null;
$reqStatus = isset($_POST['reqStatus']) ? strings_stripSlashes($_POST['reqStatus']) : null;
$reqType = isset($_POST['reqType']) ? strings_stripSlashes($_POST['reqType']) : null;
$countReq = isset($_POST['countReq']) ? strings_stripSlashes($_POST['countReq']) : null;


// 20050906 - fm
$prodID = isset($_SESSION['productID']) ? $_SESSION['productID'] : 0;
$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : 0;
$login_name = isset($_SESSION['user']) ? strings_stripSlashes($_SESSION['user']) : null;




$arrCov = null;


// 20050826 - fm
$of = new fckeditor('scope') ;
$of->BasePath = $_SESSION['basehref'] . 'third_party/fckeditor/';
$of->ToolbarSet=$g_fckeditor_toolbar;;


// create a new spec.
if(isset($_POST['createReq']))
{
	if (isset($_POST['title'])) {
		$sqlResult = createRequirement($title, $scope, $idSRS, $reqStatus); // used default type=n
		$action = 'create';
		
		$scope='';
	}
	
	$template = 'reqCreate.tpl';
	$bGetReqs = FALSE;
} 

// edit REQ
elseif (isset($_GET['editReq']))
{
	$idReq = strings_stripSlashes($_GET['editReq']);
	$arrReq = getReqData($idReq);
	// 20050930 - MHT - Added audit
	$arrReq['author'] = getUserName($arrReq['id_author']);
	$arrReq['modifier'] = getUserName($arrReq['id_modifier']);
	$arrReq['coverage'] = getTc4Req($idReq);

  // 20050826
  $scope = $arrReq['scope']; 
  $action ='editReq';
	$template = 'reqEdit.tpl';
	$bGetReqs = FALSE;
}

// update REQ
elseif (isset($_POST['updateReq']))
{
	$sqlResult = updateRequirement($idReq, $title, $scope, $reqStatus, $reqStatus);
	$action = 'update';
	$sqlItem = 'Requirement';
}

// delete REQ
elseif (isset($_POST['deleteReq']))
{
	$sqlResult = deleteRequirement($idReq);
	$action = 'delete';
}

// edit spec.
elseif (isset($_POST['editSRS']))
{
	$template = 'reqSpecEdit.tpl';
	$action="editSRS";
	
  //	$set = $id;
}

// update spec.
elseif (isset($_POST['updateSRS']))
{
	// 20050906 - fm
	$sqlResult = updateReqSpec($idSRS,$title,$scope,$countReq,$userID);
	$action = 'update';
}

elseif (isset($_POST['multiAction']))
{
	$arrIdReq = array_keys($_POST); // obtain names(id) of REQs
	array_pop($arrIdReq);	// remove multiAction value
	
	if (count($arrIdReq) != 0) {
		if ($_POST['multiAction'] == lang_get('req_select_delete')) 
		{
			foreach ($arrIdReq as $idReq) {
				tLog("Delete requirement id=" . $idReq);
				$tmpResult = deleteRequirement($idReq);
				if ($tmpResult != 'ok') {
					$sqlResult .= $tmpResult . '<br />';
				}
			}
			if (empty($sqlResult)) {
				$sqlResult = 'ok';
			}
			$action = 'delete';
		} 
		elseif ($_POST['multiAction'] == lang_get('req_select_create_tc')) 
		{
			// 20050906 - fm
			$sqlResult = createTcFromRequirement($arrIdReq, $prodID, $login_name);
			$action = 'create';
			$sqlItem = 'test case(s)';
		}
	} else {
			$sqlResult = lang_get('req_msg_noselect');
	}
}


// collect existing reqs for the SRS
if ($bGetReqs) {
	$arrReq = getRequirements($idSRS);
}
// collect existing document data
$arrSpec = getReqSpec($idSRS);
// 20050930 - MHT - Added audit
$arrSpec[0]['author'] = getUserName($arrSpec[0]['id_author']);
$arrSpec[0]['modifier'] = getUserName($arrSpec[0]['id_modifier']);

// smarty
$smarty = new TLSmarty;
$smarty->assign('arrSpec', $arrSpec);
$smarty->assign('arrReq', $arrReq);
$smarty->assign('arrCov', $arrCov);
$smarty->assign('sqlResult', $sqlResult);
$smarty->assign('sqlItem', $sqlItem);
$smarty->assign('action', $action);
$smarty->assign('name',$title); // of updated item
$smarty->assign('selectReqStatus', $arrReqStatus);
$smarty->assign('modify_req_rights', has_rights("mgt_modify_req")); 

if($scope)
{
	$of->Value=$scope;
}
else if ($action && $action != 'create')
{
	$of->Value=$arrSpec[0]['scope'];
}
else
{
	$of->Value="";
}


$smarty->assign('scope',$of->CreateHTML());
$smarty->display($template);
?>
