<h2>Files in CAD Result Directory</h2>
<p>Number of files: {$inspector_files|@count|number_format}</p>
<table class="col-tbl">
	<thead>
		<tr><th>File</th><th>Type</th><th>Size</th></tr>
	</thead>
	<tbody>
	{foreach from=$inspector_files item=item}
		<tr>
			{if $item.type=='file'}
			<td class="name themeColor"><a href="{$cadResult->webPathOfCadResult()|escape}/{$item.file|escape}">{$item.file|escape}</a></td>
			{else}
			<td class="name themeColor">{$item.file|escape}</td>
			{/if}
			<td>{$item.type|escape}</td>
			<td style="text-align: right">{$item.size|number_format}</td>
		</tr>
	{/foreach}
	</tbody>
</table>