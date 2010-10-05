<?xml version="1.0" encoding="shift_jis"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/base.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=shift_jis" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>CIRCUS CS {$smarty.session.circusVersion}</title>
<!-- InstanceEndEditable -->
<link href="css/import.css" rel="stylesheet" type="text/css" media="all" />
<script language="javascript" type="text/javascript" src="jq/jquery-1.3.2.min.js"></script>
<script language="javascript" type="text/javascript" src="jq/jq-btn.js"></script>
<script language="javascript" type="text/javascript" src="js/hover.js"></script>
<script language="javascript" type="text/javascript" src="js/viewControl.js"></script>
<script language="javascript" type="text/javascript" src="js/search_panel.js"></script>
<script language="javascript" type="text/javascript" src="js/list_tab.js"></script>
<link rel="shortcut icon" href="favicon.ico" />
<!-- InstanceBeginEditable name="head" -->
<link href="./css/mode.{$smarty.session.colorSet}.css" rel="stylesheet" type="text/css" media="all" />
<!-- InstanceEndEditable -->
<!-- InstanceParam name="class" type="text" value="patient-list" -->
</head>

<body class="patient-list spot">
<div id="page">
	<div id="container" class="menu-back">
		<div id="leftside">
			{include file='menu.tpl'}
		</div><!-- / #leftside END -->
		<div id="content">
<!-- InstanceBeginEditable name="content" -->
			<h2 class="spot">Patient list</h2>
			
		<!-- ***** Search ***** -->
			<form name="" onsubmit="return false;">
				<input type="hidden" id="hiddenFilterPtID"   value="{$param.filterPtID}" />
				<input type="hidden" id="hiddenFilterPtName" value="{$param.filterPtName}" />
				<input type="hidden" id="hiddenFilterSex"    value="{$param.filterSex}" />
				<input type="hidden" id="hiddenShowing"      value="{$param.showing}" />
				{include file='patient_search_panel.tpl'}
			</form>
		<!-- / End of search -->
			
		<!-- / Search End -->

		<!-- ***** List ***** -->
			<div class="serp">
				Showing {$param.startNum} - {$param.endNum} of {$param.totalNum} results
			</div>
			
			<table class="col-tbl" style="width:100%;">
				<thead>
					<tr>
						{foreach from=$colParam item=item}
							{if $item.colName != 'Detail'}
								<th>
									{if $param.orderCol == $item.colName}<span style="color:#fff; font-size:10px">{if $param.orderMode == "ASC"}&#9650;{else}&#9660;{/if}</span>{/if}<span><a onclick="ChangeOrderOfPatientList('{$item.colName}', '{if $param.orderCol == $item.colName && $param.orderMode == "ASC"}DESC{else}ASC{/if}');">{$item.colName}</a></span>
								</th>
							{else}
								<th>Detail</th>
							{/if}
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach from=$data item=item name=cnt}
						<tr id="row{$smarty.foreach.cnt.iteration}" {if $smarty.foreach.cnt.iteration%2==0}class="column"{/if}>
							<td class="al-l">{$item[0]}</td>
							<td class="al-l">{$item[1]}</td>
							<!-- <td>60</td> -->
							<td>{$item[2]}</td>
							<td>{$item[3]}</td>
							<td>
								<input name="" type="button" value="show" class="s-btn form-btn"
								       onclick="ShowStudyList({$smarty.foreach.cnt.iteration}, '{$item[4]}')" />
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
			

			{* ------ Footer with page list --- *}
			<div id="serp-paging" class="al-c mt10">
				{if $param.maxPageNum > 1}
					{if $param.pageNum > 1}
						<div><a href="{$param.pageAddress}&pageNum={$param.pageNum-1}"><span style="color: red">&laquo;</span>&nbsp;Previous</a></div>
					{/if}

					{if $param.startPageNum > 1}
						<div><a href="{$param.pageAddress}&pageNum=1">1</a></div>
						{if $param.startPageNum > 2}<div>...</div>{/if}
					{/if}

					{section name=i start=$param.startPageNum loop=$param.endPageNum+1}
						{assign var="i" value=$smarty.section.i.index}

			    		{if $i==$param.pageNum}
							<div><span style="color: red" class="fw-bold">{$i}</span></div>
						{else}
							<div><a href="{$param.pageAddress}&pageNum={$i}">{$i}</a></div>
						{/if}
					{/section}

					{if $param.endPageNum < $param.maxPageNum}
						{if $param.maxPageNum-1 > $param.endPageNum}<div>...</div>{/if}
						<div><a href="{$param.pageAddress}&pageNum={$param.maxPageNum}">{$param.maxPageNum}</a></div>
					{/if}

					{if $param.pageNum < $param.maxPageNum}
						<div><a href="{$param.pageAddress}&pageNum={$param.pageNum+1}">Next&nbsp;<span style="color: red">&raquo;</span></a></div>
					{/if}
				{/if}
			</div>
			{* ------ / Hooter end --- *}
		<!-- / List End -->
			
			<div class="al-r">
				<p class="pagetop"><a href="#page">page top</a></p>
			</div>
<!-- InstanceEndEditable -->
		</div><!-- / #content END -->
	</div><!-- / #container END -->
</div><!-- / #page END -->
</body>
<!-- InstanceEnd --></html>
