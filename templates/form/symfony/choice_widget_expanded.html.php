<?php
/**
 * Form template file
 *
 * Created:   April 3, 2017
 *
 * @package:  MWP Application Framework
 * @author:   Kevin Carwile
 * @since:    1.3.12
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>
<div <?php echo $view['form']->block($form, 'widget_container_attributes') ?>>
<?php foreach ($form as $child): ?>
	<?php echo $choice_prefix ?>
	<?php if (false !== $child->vars['label']): ?>
		<?php if ($child->vars['required']) { $child->vars['label_attr']['class'] = trim((isset($child->vars['label_attr']['class']) ? $child->vars['label_attr']['class'] : '').' required'); } ?>
		<?php if (!$child->vars['compound']) { $child->vars['label_attr']['for'] = $child->vars['id']; } ?>
		<?php if (!$child->vars['label']) { $label = isset($child->vars['label_format'])
			? strtr($child->vars['label_format'], array('%name%' => $name, '%id%' => $id))
			: $view['form']->humanize($child->vars['name']); } ?>
		<label <?php foreach ($child->vars['label_attr'] as $k => $v) { printf('%s="%s" ', $view->escape($k), $view->escape($v)); } ?>>
	<?php endif ?>
    <?php echo $view['form']->widget($child) ?> 
	<?php if (false !== $child->vars['label']): ?>
		<?php echo $view->escape(false !== $choice_translation_domain ? $view['translator']->trans($child->vars['label'], array(), $translation_domain) : $child->vars['label']) ?>
		</label>
	<?php endif ?>
	<?php echo $choice_suffix ?>
<?php endforeach ?>
</div>
