<?php 
//@@licence@@

class fostratAdminMediaList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No media').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear" id="stream-media-list"><tr>'.
			'<th colspan="2"></th>'.
			'<th>'.__('Title').'</th>'.
			'<th>'.__('Published on').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				//'<pre>'.print_r($this->rs).'</pre>';
				echo $this->mediaLine();
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function mediaLine()
	{
		global $fostrak;
		
		$res = '<tr class="line" id="m'.$this->rs->media_id.'">'.
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->media_id,'','','').'</td>'.
		'<td class="nowrap"><img src="'.$this->rs->media_icon.'" /></td>'.
		'<td class="maximal"><a href="media_item.php?id='.$this->rs->media_id.'#fostrak">'.
		$this->rs->relname.'</a><p><strong>'.html::escapeHTML($this->rs->media_title).'</strong>'.
		($this->rs->post_excerpt ? '&nbsp;: '.html::escapeHTML($this->rs->post_excerpt) : '').'</p>'.
		'<p><a href="'.$fostrak->getPublicUrl().$this->rs->relname.'" target="blank">'.__('View on site').'</a></p>'.
		'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->media_dtdb).'</td>'.
		'</tr>';
		
		return $res;
	}
}

?>