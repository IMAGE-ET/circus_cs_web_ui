<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<title>CIRCUS CS {$smarty.session.circusVersion}</title>

<link href="../css/import.css" rel="stylesheet" type="text/css" media="all" />
<script language="javascript" type="text/javascript" src="../jq/jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="../jq/jq-btn.js"></script>
<script language="javascript" type="text/javascript" src="../js/hover.js"></script>
<script language="javascript" type="text/javascript" src="../js/viewControl.js"></script>
<link rel="shortcut icon" href="../favicon.ico" />

<script language="Javascript">;
<!--
{literal}

function deleteStorage(storageID, type)
{
	if(confirm('Do you want to delete storage ID='+ storageID + ' ?'))
	{
		var address = 'data_storage_config.php?mode=delete'
		            + '&newStorageID=' + storageID
					+ '&newType='+ type
					+ '&ticket=' + $("#ticket").val();

		location.replace(address);	
	}
}

function AddStorage(ticket)
{
	var address = 'data_storage_config.php?mode=add'
				+ '&newPath=' + encodeURIComponent($("#newPath").val())
	            + '&newType=' + $("#typeList").val()
				+ '&ticket='  + $("#ticket").val();
	location.replace(address);
}

function ResetAddStorage()
{
	$("#newPath").val("");
	$("#typeList").children().removeAttr("selected");
}

function UpdateCurrentID(ticket)
{
	if(confirm('Do you want to change current ID?'))
	{
		var address = 'data_storage_config.php?mode=changeCurrent'
		            + '&oldDicomID=' + $("#oldDicomID").val()
		            + '&oldResultID=' + $("#oldResultID").val()
		            + '&newDicomID=' + $("#currentDICOMList").val()
		            + '&newResultID=' + $("#currentResultList").val()
					+ '&ticket=' + $("#ticket").val();

		location.replace(address);	
	}
}


function ResetCurrentID()
{
	$("#currentDICOMList").val($("#oldDicomID").val());
	$("#currentResultList").val($("#oldResultID").val());

}

{/literal}
-->
</script>


<link href="../css/mode.{$smarty.session.colorSet}.css" rel="stylesheet" type="text/css" media="all" />
<link href="../css/popup.css" rel="stylesheet" type="text/css" media="all" />
<script language="javascript" type="text/javascript" src="../js/hover.js"></script>
</head>

<body class="spot">
<div id="page">
	<div id="container" class="menu-back">
		<!-- ***** #leftside ***** -->
		<div id="leftside">
			{include file='menu.tpl'}
		</div>
		<!-- / #leftside END -->

		<div id="content">
			<h2>Data storage configuration</h2>

			<form id="form1" name="form1">
				<input type="hidden" id="ticket" value="{$ticket}" />

				<div id="message" class="mt5 mb5 ml10">{$message}</div>

				{if $restartButtonFlg == 1}
					<p class="mb5 ml10">
						<span style="color:#ff0000; font-weight:bold;">
							Please restart DICOM storage server and HTTP server to activate settings.
						</span>
						<input type="button" class="form-btn" value="restart" onclick="RestartServer('{$ticket}');">
					</p>
				{/if}

				<div id="storageList" class="ml10 mb20">
					<table class="col-tbl">
						<thead>
							<tr>
								<th>ID</th>
								<th>Path</th>
								<th>Type</th>
								<th>Current Use</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
						{foreach from=$storageList item=item name=cnt}
							<tr {if $smarty.foreach.cnt.iteration%2==0}class="column"{/if}>
								<td>{$item[0]}</td>
								<td class="al-l">{$item[1]}</td>
								<td>{if $item[2]==1}DICOM storage{else}Plug-in result{/if}</td>
								<td>{if $item[3]==true}TRUE{else}FALSE{/if}</td>
								<td>
									<input type="button" id="deleteButton{$smarty.foreach.cnt.iteration}" value="delete"
										{if $item[0] != $smarty.session.userID}
										 	class="s-btn form-btn" onclick="deleteStorage({$item[0]}, {$item[2]});" />
									 	{else}
										 	name="loginUser" class="s-btn form-btn form-btn-disabled" disabled="disabled" />
										{/if}
								</td>
							</tr>
						{/foreach}
						</tbody>
					</table>
				</div>

				<h3>Add storage</h3>
				<div class="mt10 ml40">
					<table class="detail-tbl">
						<tr>
							<th style="width: 5em;"><span class="trim01">Path</th>
							<td><input type="text" size="60" id="newPath" /></td>
						</tr>
						
						<tr>
							<th><span class="trim01">Type</th>
							<td>
								<select id="typeList">
									<option value="1">DICOM storage</option>
									<option value="2">Plug-in result</option>
								</select>
							</td>
						</tr>

					</table>

					<div class="pl20 mb20 mt10">
						<p>
							<input type="button" class="form-btn" value="add"   onclick="AddStorage('{$ticket}');" />&nbsp;
							<input type="button" class="form-btn" value="reset" onclick="ResetAddStorage();" />
						</p>
					</div>
				</div>

				<h3>Change current storage id</h3>
				<div class="mt10 ml40">

					<input type="hidden" id="oldDicomID"  value="{$oldDicomID}" />
					<input type="hidden" id="oldResultID" value="{$oldResultID}" />

					<table class="detail-tbl">
						<tr>
							<th style="width: 10em;"><span class="trim01">DICOM storage</th>
							<td>
								<select id="currentDICOMList" style="width: 3.5em;">
									{foreach from=$storageList item=item name=cnt}
										{if $item[2]==1}
											<option value="{$item[0]}"{if $item[3]==true} selected="selected"{/if}>{$item[0]}</option>
										{/if}
									{/foreach}
								</select>
							</td>
						</tr>

						<tr>
							<th><span class="trim01">Plug-in result</th>
							<td>
								<select id="currentResultList" style="width: 3.5em;">
									{foreach from=$storageList item=item name=cnt}
										{if $item[2]==2}
											<option value="{$item[0]}"{if $item[3]==true} selected="selected"{/if}>{$item[0]}</option>
										{/if}
									{/foreach}
								</select>
							</td>
						</tr>
						
					</table>

					<div class="pl20 mb20 mt10">
						<p>
							<input type="button" class="form-btn" value="update" onclick="UpdateCurrentID('{$ticket}');" />&nbsp;
							<input type="button" class="form-btn" value="reset"  onclick="ResetCurrentID();" />
						</p>
					</div>
			</form>
		</div><!-- / #content END -->
	</div><!-- / #container END -->
</div><!-- / #page END -->
</body>
</html>
