$(function(){
	/***** ���[���I�[�o�[ *****/
	$('.jq-btn').bind('mouseover',
		function(){$(this).css('background-position','0 '+$.curCSS(this,'height'));}
	).bind('mouseout',
		function(){$(this).css('background-position','0 0');}
	);
	// �N���b�N��
	//	.mousedown(function(){
	//	$(this).css('background-position','0 -'+($(this).height() * 2)+'px');
	//}).mouseup(function(){
	//	$(this).css('background-position','0 -'+$(this).height()+'px');
	//});
	if($('body').attr('id')){
		$('.jq-btn').filter('.'+$('body').attr('id')).each(function(){
			var h=$.curCSS(this,'height');
			$(this).unbind()
			.css({backgroundPosition:'0 '+h, cursor:'default'})
			.unbind('click').bind('click',function(){return false});
		})
	}
});

$(function(){
	/***** ���[���I�[�o�[ *****/
	$('.jq-btn-ctr').bind('mouseover',
		function(){$(this).css('background-position','0 '+$.curCSS(this,'height'));}
	).bind('mouseout',
		function(){$(this).css('background-position','0 0');}
	);
	// �N���b�N��
	//	.mousedown(function(){
	//	$(this).css('background-position','0 -'+($(this).height() * 2)+'px');
	//}).mouseup(function(){
	//	$(this).css('background-position','0 -'+$(this).height()+'px');
	//});
	if($('body').attr('id')){
		$('.jq-btn-ctr').filter('.'+$('body').attr('id')).each(function(){
			var h=$.curCSS(this,'height');
			$(this).unbind()
			.css({backgroundPosition:'0 '+h, cursor:'default'})
			.unbind('click').bind('click',function(){return false});
		})
	}
});

$(function(){
	/***** ���[���I�[�o�[ *****/
	$('.jq-btn-right').bind('mouseover',
		function(){$(this).css('background-position','0 '+$.curCSS(this,'height'));}
	).bind('mouseout',
		function(){$(this).css('background-position','0 0');}
	);
	// �N���b�N��
	//	.mousedown(function(){
	//	$(this).css('background-position','0 -'+($(this).height() * 2)+'px');
	//}).mouseup(function(){
	//	$(this).css('background-position','0 -'+$(this).height()+'px');
	//});
	if($('body').attr('id')){
		$('.jq-btn-right').filter('.'+$('body').attr('id')).each(function(){
			var h=$.curCSS(this,'height');
			$(this).unbind()
			.css({backgroundPosition:'0 '+h, cursor:'default'})
			.unbind('click').bind('click',function(){return false});
		})
	}
});