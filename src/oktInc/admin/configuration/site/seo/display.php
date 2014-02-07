<?php
/**
 * Configuration du site référencement (partie affichage)
 *
 * @addtogroup Okatea
 *
 */


# Accès direct interdit
if (!defined('ON_CONFIGURATION_MODULE')) die;

?>

<h3><?php _e('c_a_config_tab_seo') ?></h3>

<?php foreach ($okt->languages->list as $aLanguage) : ?>

<p class="field" lang="<?php echo $aLanguage['code'] ?>"><label for="p_title_tag_<?php echo $aLanguage['code'] ?>"><?php _e('c_a_config_title_tag') ?><span class="lang-switcher-buttons"></span></label>
<?php echo form::text(array('p_title_tag['.$aLanguage['code'].']','p_title_tag_'.$aLanguage['code']), 60, 255, (isset($okt->config->title_tag[$aLanguage['code']]) ? html::escapeHTML($okt->config->title_tag[$aLanguage['code']]) : '')) ?>
<span class="note"><?php _e('c_a_config_title_tag_note') ?></span></p>

<p class="field" lang="<?php echo $aLanguage['code'] ?>"><label for="p_meta_description_<?php echo $aLanguage['code'] ?>"><?php _e('c_c_seo_meta_desc') ?><span class="lang-switcher-buttons"></span></label>
<?php echo form::text(array('p_meta_description['.$aLanguage['code'].']','p_meta_description_'.$aLanguage['code']), 60, 255, (isset($okt->config->meta_description[$aLanguage['code']]) ? html::escapeHTML($okt->config->meta_description[$aLanguage['code']]) : '')) ?></p>

<p class="field" lang="<?php echo $aLanguage['code'] ?>"><label for="p_meta_keywords_<?php echo $aLanguage['code'] ?>"><?php _e('c_c_seo_meta_keywords') ?><span class="lang-switcher-buttons"></span></label>
<?php echo form::textarea(array('p_meta_keywords['.$aLanguage['code'].']','p_meta_keywords_'.$aLanguage['code']), 57, 5, (isset($okt->config->meta_keywords[$aLanguage['code']]) ? html::escapeHTML($okt->config->meta_keywords[$aLanguage['code']]) : '')) ?></p>

<?php endforeach; ?>

