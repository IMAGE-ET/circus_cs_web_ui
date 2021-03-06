{capture name="require"}
jq/ui/jquery-ui.min.js
jq/jquery.blockUI.js
js/search_panel.js
js/edit_tags.js
js/download_volume.js
jq/ui/theme/jquery-ui.custom.css
jq/jquery.mousewheel.js
js/jquery.imageviewer.js
css/darkroom.css
{/capture}
{capture name="extra"}
<script type="text/javascript" language="Javascript">

var sid = "{$series->sid|escape:javascript}";
var seriesInstanceUID = "{$series->series_instance_uid|escape:javascript}";
var viewer = {$viewer|@json_encode};

{literal}

$(function() {
	// sets up image viewer
	var viewer_params = {
		source: new DicomDynamicImageSource(seriesInstanceUID, ''),
		maxWidth: 500
	};
	$.extend(viewer_params, viewer);
	var v = $('#series-detail-viewer').imageviewer(viewer_params);
	v.bind('imagechange', function() {
		$('#slice-number').text(v.imageviewer('option', 'index'));
		$('#slice-location').text(v.imageviewer('option', 'sliceLocation'));
	});

	// sets up volume download button
	$('#download').click(function() {
		var series_uid = $('#seriesInstanceUID').val();
		circus.download_volume.openDialogForSeries(series_uid);
	});

	// tag editor
	var refresh = function(tags) {
		$('#series-tags').refreshTags(tags, 'series_list.php', 'filterTag');
	};
	$('#edit-tag').click(function() {
		circus.edittag.openEditor(3, sid, refresh);
	});
	circus.edittag.load(3, sid, refresh);
});

</script>

<style type="text/css">
#download-panel { margin: 10px 0 0 15px; }
</style>
{/literal}
{/capture}
{include file="header.tpl" require=$smarty.capture.require
	head_extra=$smarty.capture.extra}

<!-- ***** TAB ***** -->
<div class="tabArea">
	<ul>
		<li><a href="series_list.php?mode=study&studyInstanceUID={$study->study_instance_uid|escape:url}" class="btn-tab">Series list</a></li>
		<li><a href="" class="btn-tab btn-tab-active">Series detail</a></li>
	</ul>
</div><!-- / .tabArea END -->

<div class="tab-content">
{if $data.errorMessage != ""}
	<div style="color:#f00; font-weight:bold;">{$data.errorMessage|escape|nl2br}</div>
{else}
	<div id="series_detail">
		<h2>Series detail</h2>

		<div class="series-detail-img">
			<div id="series-detail-viewer"></div>{* Viewer Placeholder *}
		</div>

		<div class="detail-panel">
			<table class="detail-tbl">
				<tr>
					<th style="width: 12em;"><span class="trim01">Patient ID</span></th>
					<td>{$patient->patient_id|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Patient name</span></th>
					<td>{$patient->patient_name|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Sex</span></th>
					<td>{$patient->sex|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Age</span></th>
					<td>{$study->age|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Study ID</span></th>
					<td>{$study->study_id|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Series date</span></th>
					<td>{$series->series_date|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Series time</span></th>
					<td>{$series->series_time|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Modality</span></th>
					<td>{$series->modality|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Series description</span></th>
					<td>{$series->series_description|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Body part</span></th>
					<td>{$series->body_part|escape}</td>
				</tr>
				<tr>
					<th><span class="trim01">Image number</span></th>
					<td><span id="slice-number"></span></td>
				</tr>
				<tr>
					<th><span class="trim01">Slice location</span></th>
					<td><span id="slice-location"></span></td>
				</tr>
			</table>
			{if $currentUser->hasPrivilege('volumeDownload')}
			<form id="dl-form">
				<div id="download-panel">
					<input type="button" id="download" value="Download volume data" class="form-btn"/>
				</div>
				<input type="hidden" id="seriesInstanceUID" name="seriesInstanceUID" value="{$series->series_instance_uid|escape}" />
			</form>
			{/if}
		</div><!-- / .detail-panel END -->
		<div style="clear: both"></div>
	</div>
	<!-- / Series detail END -->

	<div id="tagArea">
		Tags: <span id="series-tags">Loading Tags...</span>
		{if $smarty.session.personalFBFlg==1}<a href="#" id="edit-tag">(Edit)</a>{/if}
	</div>

	<div class="al-r">
		<p class="pagetop"><a href="#page">page top</a></p>
	</div>
{/if}
</div><!-- / .tab-content END -->

<!-- darkroom button -->
{include file='darkroom_button.tpl'}

{include file="footer.tpl"}