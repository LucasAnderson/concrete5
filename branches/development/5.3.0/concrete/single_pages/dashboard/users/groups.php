<?
defined('C5_EXECUTE') or die(_("Access Denied."));
$section = 'groups';

if ($_REQUEST['task'] == 'edit') {
	$g = Group::getByID($_REQUEST['gID']);
	if (is_object($g)) { 		
		if ($_POST['update']) {
		
			$gName = $_POST['gName'];
			$gDescription = $_POST['gDescription'];
			
		} else {
			
			$gName = $g->getGroupName();
			$gDescription = $g->getGroupDescription();
		
		}
		
		$editMode = true;
	}
}

$txt = Loader::helper('text');
$ih = Loader::helper('concrete/interface');
$valt = Loader::helper('validation/token');

if ($_POST['add'] || $_POST['update']) {

	$gName = $txt->sanitize($_POST['gName']);
	$gDescription = $_POST['gDescription'];
	
	$error = array();
	if (!$gName) {
		$error[] = t("Name required.");
	}
	
	if (!$valt->validate('add_or_update_group')) {
		$error[] = $valt->getErrorMessage();
	}
	
	$g1 = Group::getByName($gName);
	if ($g1 instanceof Group) {
		if ((!is_object($g)) || $g->getGroupID() != $g1->getGroupID()) {
			$error[] = t('A group named "%s" already exists', $g1->getGroupName());
		}
	}
	
	if (count($error) == 0) {
		if ($_POST['add']) {
			$g = Group::add($gName, $_POST['gDescription']);
			$this->controller->redirect('/dashboard/users/groups?created=1');
		} else if (is_object($g)) {
			$g->update($gName, $_POST['gDescription']);
			$this->controller->redirect('/dashboard/users/groups?updated=1');
		}		
		exit;
	}
}

if ($_GET['created']) {
	$message = t("Group Created.");
} else if ($_GET['updated']) {
	$message = t("Group Updated.");
}

if (!$editMode) {

Loader::model('search/group');
$gl = new GroupSearch();
if (isset($_GET['gKeywords'])) {
	$gl->filterByKeywords($_GET['gKeywords']);
}

$gResults = $gl->getPage();

?>

<h1><span><?=t('Groups')?></span></h1>
<div class="ccm-dashboard-inner">


<form id="ccm-group-search" method="get" style="top: -30px; left: 10px" action="<?=$this->url('/dashboard/users/groups')?>">
<div id="ccm-group-search-fields">
<input type="text" id="ccm-group-search-keywords" name="gKeywords" value="<?=$_REQUEST['gKeywords']?>" class="ccm-text" style="width: 100px" />
<input type="submit" value="<?=t('Search')?>" />
<input type="hidden" name="group_submit_search" value="1" />
</div>
</form>

<? if (count($gResults) > 0) { 
	$gl->displaySummary();
	
foreach ($gResults as $g) { ?>

	<div class="ccm-group">
		<a class="ccm-group-inner" href="<?=$this->url('/dashboard/users/groups?task=edit&gID=' . $g['gID'])?>" style="background-image: url(<?=ASSETS_URL_IMAGES?>/icons/group.png)"><?=$g['gName']?></a>
		<div class="ccm-group-description"><?=$g['gDescription']?></div>
	</div>


<? }

	$gl->displayPaging();

} else { ?>

	<p><?=t('No groups found.')?></p>
	
<? } ?>

</div>

<h1><span><?=t('Add Group')?> (<em class="required">*</em> - <?=t('required field')?>)</span></h1>

<div class="ccm-dashboard-inner">

<form method="post" id="add-group-form" action="<?=$this->url('/dashboard/users/groups/')?>">
<?=$valt->output('add_or_update_group')?>
<div style="margin:0px; padding:0px; width:100%; height:auto" >	
<table class="entry-form" border="0" cellspacing="1" cellpadding="0">
<tr>
	<td class="subheader" colspan="3"><?=t('Name')?> <span class="required">*</span></td>
</tr>
<tr>
	<td colspan="3"><input type="text" name="gName" style="width: 100%" value="<?=$_POST['gName']?>" /></td>
</tr>
<tr>
	<td class="subheader" colspan="3"><?=t('Description')?></td>
</tr>
<tr>
	<td colspan="3"><textarea name="gDescription" style="width: 100%; height: 120px"><?=$_POST['gDescription']?></textarea></td>
</tr>
<tr>
	<td colspan="3" class="header"><input type="hidden" name="add" value="1" /><?=$ih->submit(t('Add'), 'add-group-form')?></td>
</tr>
</table>
</div>
<br>
</form>	
</div>



<? } else { ?>
	<h1><span><?=t('Edit Group')?></span></h1>
	<div class="ccm-dashboard-inner">
	
		<form method="post" id="update-group-form" action="<?=$this->url('/dashboard/users/groups/')?>">
		<?=$valt->output('add_or_update_group')?>
		<input type="hidden" name="gID" value="<?=$_REQUEST['gID']?>" />
		<input type="hidden" name="task" value="edit" />
		
		<div style="margin:0px; padding:0px; width:100%; height:auto" >	
		<table class="entry-form" border="0" cellspacing="1" cellpadding="0">
		<tr>
			<td class="subheader" colspan="3"><?=t('Name')?> <span class="required">*</span></td>
		</tr>
		<tr>
			<td colspan="3"><input type="text" name="gName" style="width: 100%" value="<?=$gName?>" /></td>
		</tr>
		<tr>
			<td class="subheader" colspan="3"><?=t('Description')?></td>
		</tr>
		<tr>
			<td colspan="3"><textarea name="gDescription" style="width: 100%; height: 120px"><?=$gDescription?></textarea></td>
		</tr>
		<tr>
			<td colspan="3" class="header">
			<input type="hidden" name="update" value="1" />
			<?=$ih->submit(t('Update'), 'update-group-form')?>
			<?=$ih->button(t('Cancel'), $this->url('/dashboard/users/groups'), 'left')?>
			</td>
		</tr>
		</table>
		</div>
		
		<br>
		</form>	
	</div>
	
	<h1><span><?=t('Delete Group')?></span></h1>
	
	<div class="ccm-dashboard-inner">
		<?
		$u=new User();

		$delConfirmJS = t('Are you sure you want to permanently remove this group?');
		if($u->isSuperUser() == false){ ?>
			<?=t('You must be logged in as %s to remove groups.', USER_SUPER)?>			
		<? }else{ ?>   

			<script type="text/javascript">
			deleteGroup = function() {
				if (confirm('<?=$delConfirmJS?>')) { 
					location.href = "<?=$this->url('/dashboard/users/groups', 'delete', $_REQUEST['gID'], $valt->generate('delete_group_' . $_REQUEST['gID']))?>";				
				}
			}
			</script>

			<? print $ih->button_js(t('Delete Group'), "deleteGroup()", 'left');?>

		<? } ?>
		<div class="ccm-spacer"></div>
	</div>	
	<?   
}