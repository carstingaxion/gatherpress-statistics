<?php
// This file is generated. Do not modify it manually.
return array(
	'build' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'gatherpress/statistics',
		'version' => '0.1.0',
		'title' => 'GatherPress Statistics',
		'keywords' => array(
			'statistics',
			'counters',
			'gatherpress',
			'events'
		),
		'category' => 'widgets',
		'icon' => 'chart-bar',
		'description' => 'Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.',
		'example' => array(
			'attributes' => array(
				'statisticType' => 'total_events',
				'labelSingular' => 'Event',
				'labelPlural' => 'Events',
				'eventQuery' => 'past'
			)
		),
		'attributes' => array(
			'statisticType' => array(
				'type' => 'string',
				'default' => 'total_events'
			),
			'labelSingular' => array(
				'type' => 'string',
				'default' => 'Event'
			),
			'labelPlural' => array(
				'type' => 'string',
				'default' => 'Events'
			),
			'selectedTaxonomyTerms' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'selectedTerm' => array(
				'type' => 'number',
				'default' => 0
			),
			'selectedTaxonomy' => array(
				'type' => 'string',
				'default' => ''
			),
			'countTaxonomy' => array(
				'type' => 'string',
				'default' => ''
			),
			'filterTaxonomy' => array(
				'type' => 'string',
				'default' => ''
			),
			'eventQuery' => array(
				'type' => 'string',
				'default' => 'past',
				'enum' => array(
					'upcoming',
					'past'
				)
			),
			'showLabel' => array(
				'type' => 'boolean',
				'default' => true
			),
			'prefixDefault' => array(
				'type' => 'string',
				'default' => ''
			),
			'suffixDefault' => array(
				'type' => 'string',
				'default' => ''
			),
			'prefixConditional' => array(
				'type' => 'string',
				'default' => ''
			),
			'suffixConditional' => array(
				'type' => 'string',
				'default' => ''
			),
			'conditionalThreshold' => array(
				'type' => 'number',
				'default' => 10
			)
		),
		'supports' => array(
			'html' => false,
			'align' => true,
			'color' => array(
				'background' => true,
				'text' => true,
				'gradients' => true,
				'link' => true
			),
			'spacing' => array(
				'padding' => true,
				'margin' => true,
				'blockGap' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalTextDecoration' => true
			),
			'shadow' => true,
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			)
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Counter',
				'isDefault' => true
			),
			array(
				'name' => 'card',
				'label' => 'Card'
			),
			array(
				'name' => 'minimal',
				'label' => 'Minimal'
			),
			array(
				'name' => 'confetti',
				'label' => 'Confetti'
			)
		),
		'textdomain' => 'gatherpress-statistics',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
