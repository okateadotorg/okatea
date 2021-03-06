<?php
/**
 * @ingroup okt_module_rte_CKEditor
 * @brief La classe principale du module.
 *
 */
use Okatea\Tao\Modules\Module;

class module_rte_ckeditor extends Module
{

	public $config = null;

	protected function prepend_admin()
	{
		$this->okt->page->addRte('ckeditor_simple', 'CKEditor simple', array(
			'module_rte_ckeditor',
			'CKEditorSimple'
		));
		$this->okt->page->addRte('ckeditor_advanced', 'CKEditor advanced', array(
			'module_rte_ckeditor',
			'CKEditorAdvanced'
		));
		$this->okt->page->addRte('ckeditor_Complete', 'CKEditor complete', array(
			'module_rte_ckeditor',
			'CKEditorComplete'
		));
	}

	public static function CKEditorSimple($element = 'textarea', $user_options = [])
	{
		global $okt;
		
		$options = array(
			'customConfig' => '',
			'toolbar' => 'Basic',
			'language' => $okt['visitor']->language,
			'scayt_autoStartup' => false
		);
		
		if (!empty($user_options))
		{
			$options = array_merge($options, $user_options);
		}
		
		self::getCKEditorScript($element, $options);
	}

	public static function CKEditorAdvanced($element = 'textarea', $user_options = [])
	{
		global $okt;
		
		$options = array(
			'customConfig' => '',
			'language' => $okt['visitor']->language,
			'scayt_autoStartup' => false,
			'plain/text' => "toolbar : [
					['Cut','Copy','Paste','PasteText','PasteFromWord'],
					['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
					['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar'],
					['Maximize'],
					'/',
					['Format'],
					['Bold','Italic','Strike'],
					['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
					['Link','Unlink','Anchor'],
					['Preview','Source']
				]",
			
			'filebrowserBrowseUrl' => '/ckfinder/ckfinder.html',
			'filebrowserImageBrowseUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/ckfinder.html?Type=Images',
			'filebrowserFlashBrowseUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/ckfinder.html?Type=Flash',
			'filebrowserUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
			'filebrowserImageUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
			'filebrowserFlashUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
		)
		;
		
		if (!empty($user_options))
		{
			$options = array_merge($options, $user_options);
		}
		
		self::getCKEditorScript($element, $options);
	}

	public static function CKEditorComplete($element = 'textarea', $user_options = [])
	{
		global $okt;
		
		$options = array(
			'customConfig' => '',
			'language' => $okt['visitor']->language,
			'scayt_autoStartup' => false,
			'filebrowserBrowseUrl' => '/ckfinder/ckfinder.html',
			'filebrowserImageBrowseUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/ckfinder.html?Type=Images',
			'filebrowserFlashBrowseUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/ckfinder.html?Type=Flash',
			'filebrowserUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
			'filebrowserImageUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
			'filebrowserFlashUploadUrl' => $this->okt['modules_url'] . '/rte_ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
		);
		
		if (!empty($user_options))
		{
			$options = array_merge($options, $user_options);
		}
		
		self::getCKEditorScript($element, $options);
	}

	protected static function getCKEditorScript($element, $options)
	{
		global $okt;
		
		$aElements = explode(',', $element);
		
		$okt->page->js->addFile($this->okt['modules_url'] . '/rte_ckeditor/ckeditor/ckeditor.js');
		$okt->page->js->addFile($this->okt['modules_url'] . '/rte_ckeditor/ckeditor/adapters/jquery.js');
		foreach ($aElements as $sElement)
			$okt->page->js->addReady('
			jQuery("' . $sElement . '").ckeditor( function() { /* callback code */ }, ' . json_encode($options) . ' );
			jQuery("' . $sElement . '").closest("form").find(":submit").click(function() {
				CKEDITOR.instances.' . (strpos($sElement, '#') === 0 ? substr($sElement, 1) : $sElement) . '.updateElement();
			});
		');
	}
}
