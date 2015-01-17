<?php namespace Eorzea\Forum\Models;

use Eorzea\Forum\Models\Thread;
use Eorzea\Forum\AccessControl;

use Str;
use Config;

class Category extends AbstractBaseModel {

	protected $table      = 'forum_categories';
	public    $timestamps = false;
	protected $appends    = ['threadCount', 'replyCount', 'URL', 'postAlias'];

	public function parentCategory()
	{
		return $this->belongsTo('\Eorzea\Forum\Models\Category', 'parent_category')->orderBy('weight');
	}

	public function subcategories()
	{
		return $this->hasMany('\Eorzea\Forum\Models\Category', 'parent_category')->orderBy('weight');
	}

	public function threads()
	{
		return $this->hasMany('\Eorzea\Forum\Models\Thread', 'parent_category')->with('category', 'posts')->orderBy('created_at', 'desc');
	}

	public function scopeWhereTopLevel($query)
	{
		return $query->where('parent_category', '=', NULL);
	}

	public function getThreadCountAttribute()
	{
		return $this->rememberAttribute('threadCount', function(){
			return $this->threads->count();
		});
	}

	public function getReplyCountAttribute()
	{
		return $this->rememberAttribute('replyCount', function(){
			$replyCount = 0;

			$threads = $this->threads()->get(array('id'));

			foreach ($threads as $thread) {
				$replyCount += $thread->posts->count();
			}

			return $replyCount;
		});
	}

	public function getURLAttribute()
	{
		return route('forum.get.view.category',
			array(
				'categoryID'		=> $this->id,
				'categoryAlias'	=> Str::slug($this->title, '-')
			)
		);
	}

	public function getPostAliasAttribute()
	{
		return route('forum.post.create.thread',
			array(
				'categoryID'		=> $this->id,
				'categoryAlias'	=> Str::slug($this->title, '-')
			)
		);
	}

	public function getCanPostAttribute()
	{
		return AccessControl::check($this, 'access_category', FALSE);
	}


}
