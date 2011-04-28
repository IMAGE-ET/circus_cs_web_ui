{*
Smarty Template for General CAD Result.
*}
{capture name="require"}
js/radio-to-button.js
js/cad_result.js
{foreach from=$displayPresenter->requiringFiles() item=file}{$file}{/foreach}
{foreach from=$feedbackListener->requiringFiles() item=file}{$file}{/foreach}
{/capture}
{capture name="extra"}
<script type="text/javascript">
data = {$displays|@json_encode};
feedbacks = {$feedbacks|@json_encode};
</script>
{/capture}
{include file="header.tpl" body_class="cad-result"
	require=$smarty.capture.require head_extra=$smarty.capture.extra}
<div id="cadResultTab" class="tabArea">
<ul>
	<li><a class="btn-tab" href="#">CAD Result</a></li>
</ul>
</div>
<div class="tab-content">
<div class="cadResult">
<h2>CAD Result [{$cadResult->job_id}]</h2>
  <div class="headerArea">
    NAME NAME / MRA / HEADER PENDING.
  </div>
  <div>
    <input type="radio" class="radio-to-button-l" name="mode" value="1" label="Personal Mode"/>
    <input type="radio" class="radio-to-button-l" name="mode" value="2" label="Consensual Mode"/>
  </div>
  <div style="clear: both"></div>

{include file="cad_results/block_layout.tpl"}


</div><!-- /cadResult -->
</div><!-- /tab-content -->
{include file="footer.tpl"}