/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
/* eslint-disable @wordpress/no-unsafe-wp-apis */
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	FormTokenField,
	__experimentalNumberControl as NumberControl,
	Notice,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               - Component props.
 * @param {Object}   props.attributes    - Block attributes containing:
 *                                       - {string} statisticType - Type of statistic to display
 *                                       - {string} labelSingular - Singular label for the statistic
 *                                       - {string} labelPlural - Plural label for the statistic
 *                                       - {Object} selectedTaxonomyTerms - Selected terms for multi-taxonomy filter
 *                                       - {number} selectedTerm - Selected term ID for single taxonomy filter
 *                                       - {string} selectedTaxonomy - Selected taxonomy slug for single taxonomy filter
 *                                       - {string} countTaxonomy - Taxonomy slug to count terms from
 *                                       - {string} filterTaxonomy - Taxonomy slug to filter by
 *                                       - {boolean} useContextTerm - Derive the single-taxonomy term from the post this block is placed on
 *                                       - {string[]} contextTaxonomies - Multi-taxonomy slugs whose term should come from the post this block is placed on
 *                                       - {string} eventQuery - Event query type ('upcoming' or 'past')
 *                                       - {boolean} showLabel - Whether to show the label
 *                                       - {string} prefixDefault - Default prefix text
 *                                       - {string} suffixDefault - Default suffix text
 *                                       - {string} prefixConditional - Conditional prefix text
 *                                       - {string} suffixConditional - Conditional suffix text
 *                                       - {number} conditionalThreshold - Threshold for conditional prefix/suffix
 * @param {Function} props.setAttributes - Function to update block attributes.
 * @param {Object}   props.clientId      - Unique client ID for the block instance.
 *
 * @return {Element} React element rendered in the editor.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		statisticType,
		labelSingular,
		labelPlural,
		selectedTaxonomyTerms,
		selectedTerm,
		selectedTaxonomy,
		countTaxonomy,
		filterTaxonomy,
		useContextTerm,
		contextTaxonomies,
		eventQuery,
		showLabel,
		prefixDefault,
		suffixDefault,
		prefixConditional,
		suffixConditional,
		conditionalThreshold,
	} = attributes;

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// Generate random preview count (between 1 and 100)
	const [ previewCount ] = useState(
		() => Math.floor( Math.random() * 100 ) + 1
	);

	// State for filtered taxonomies and supported types from REST API
	const [ filteredTaxonomies, setFilteredTaxonomies ] = useState( [] );
	const [ supportedTypes, setSupportedTypes ] = useState( [] );
	const [ isLoadingTaxonomies, setIsLoadingTaxonomies ] = useState( true );
	const [ isLoadingTypes, setIsLoadingTypes ] = useState( true );

	// Fetch filtered taxonomies from REST API
	useEffect( () => {
		setIsLoadingTaxonomies( true );
		apiFetch( { path: '/gatherpress-statistics/v1/taxonomies' } )
			.then( ( taxonomies ) => {
				setFilteredTaxonomies( taxonomies );
				setIsLoadingTaxonomies( false );
			} )
			.catch( () => {
				setFilteredTaxonomies( [] );
				setIsLoadingTaxonomies( false );
			} );
	}, [] );

	// Fetch supported statistic types from REST API
	useEffect( () => {
		setIsLoadingTypes( true );
		apiFetch( { path: '/gatherpress-statistics/v1/supported-types' } )
			.then( ( types ) => {
				setSupportedTypes( types );
				setIsLoadingTypes( false );
			} )
			.catch( () => {
				setSupportedTypes( [] );
				setIsLoadingTypes( false );
			} );
	}, [] );

	// CRITICAL: For total_attendees, always set eventQuery to 'past' internally
	useEffect( () => {
		if ( statisticType === 'total_attendees' ) {
			// Always ensure eventQuery is 'past' for total_attendees
			if ( eventQuery !== 'past' ) {
				setAttributes( { eventQuery: 'past' } );
			}
			// For other types, ensure eventQuery has a valid value
		} else if (
			! eventQuery ||
			! [ 'upcoming', 'past' ].includes( eventQuery )
		) {
			setAttributes( { eventQuery: 'past' } );
		}
	}, [ statisticType, eventQuery, setAttributes ] );

	// Fetch terms for all filtered taxonomies
	const allTaxonomyTerms = useSelect(
		( select ) => {
			if ( ! filteredTaxonomies || filteredTaxonomies.length === 0 ) {
				return {};
			}

			const { getEntityRecords } = select( 'core' );
			const termsMap = {};

			filteredTaxonomies.forEach( ( taxonomy ) => {
				const terms = getEntityRecords( 'taxonomy', taxonomy.slug, {
					per_page: -1,
				} );
				if ( terms ) {
					termsMap[ taxonomy.slug ] = terms;
				}
			} );

			return termsMap;
		},
		[ filteredTaxonomies ]
	);

	// Update block name dynamically based on configuration
	useEffect( () => {
		let blockName = '';

		// Build name based on statistic type
		switch ( statisticType ) {
			case 'total_events':
				blockName = sprintf(
					/* translators: %s: plural post type label */
					__( 'Total %s', 'gatherpress-statistics' ),
					labelPlural
				);
				break;
			case 'total_attendees':
				blockName = __( 'Total Attendees', 'gatherpress-statistics' );
				break;
			case 'events_per_taxonomy':
				if ( selectedTaxonomy && useContextTerm ) {
					const taxonomy = filteredTaxonomies?.find(
						( t ) => t.slug === selectedTaxonomy
					);
					blockName = sprintf(
						/* translators: %s: taxonomy name */
						__(
							'%s: current post\u2019s term',
							'gatherpress-statistics'
						),
						taxonomy ? taxonomy.name : labelPlural
					);
				} else if ( selectedTaxonomy && selectedTerm ) {
					const taxonomy = filteredTaxonomies?.find(
						( t ) => t.slug === selectedTaxonomy
					);
					const term = allTaxonomyTerms[ selectedTaxonomy ]?.find(
						( t ) => t.id === selectedTerm
					);
					if ( taxonomy && term ) {
						blockName = sprintf(
							/* translators: 1: taxonomy name, 2: term name */
							__( '%1$s: %2$s', 'gatherpress-statistics' ),
							taxonomy.name,
							term.name
						);
					} else {
						blockName = sprintf(
							/* translators: %s: plural post type label */
							__( '%s per Taxonomy', 'gatherpress-statistics' ),
							labelPlural
						);
					}
				} else {
					blockName = sprintf(
						/* translators: %s: plural post type label */
						__( '%s per Taxonomy', 'gatherpress-statistics' ),
						labelPlural
					);
				}
				break;
			case 'events_multi_taxonomy':
				if ( contextTaxonomies && contextTaxonomies.length > 0 ) {
					const contextNames = contextTaxonomies
						.map(
							( slug ) =>
								filteredTaxonomies?.find(
									( t ) => t.slug === slug
								)?.name || slug
						)
						.join( ', ' );
					blockName = sprintf(
						/* translators: 1: plural post type label, 2: comma-separated list of taxonomy names */
						__(
							'%1$s: %2$s from current post',
							'gatherpress-statistics'
						),
						labelPlural,
						contextNames
					);
				} else if (
					selectedTaxonomyTerms &&
					Object.keys( selectedTaxonomyTerms ).length > 0
				) {
					const termNames = [];
					Object.entries( selectedTaxonomyTerms ).forEach(
						( [ taxSlug, termIds ] ) => {
							if ( termIds && termIds.length > 0 ) {
								const terms = allTaxonomyTerms[ taxSlug ] || [];
								termIds.forEach( ( termId ) => {
									const term = terms.find(
										( t ) => t.id === termId
									);
									if ( term ) {
										termNames.push( term.name );
									}
								} );
							}
						}
					);
					if ( termNames.length > 0 ) {
						blockName = sprintf(
							/* translators: 1: plural post type label, 2: comma-separated list of terms */
							__( '%1$s: %2$s', 'gatherpress-statistics' ),
							labelPlural,
							termNames.join( ', ' )
						);
					} else {
						blockName = sprintf(
							/* translators: %s: plural post type label */
							__(
								'%s (Multiple Taxonomies)',
								'gatherpress-statistics'
							),
							labelPlural
						);
					}
				} else {
					blockName = sprintf(
						/* translators: %s: plural post type label */
						__(
							'%s (Multiple Taxonomies)',
							'gatherpress-statistics'
						),
						labelPlural
					);
				}
				break;
			case 'total_taxonomy_terms':
				if ( selectedTaxonomy ) {
					const taxonomy = filteredTaxonomies?.find(
						( t ) => t.slug === selectedTaxonomy
					);
					if ( taxonomy ) {
						blockName = sprintf(
							/* translators: %s: taxonomy name */
							__( 'Total %s', 'gatherpress-statistics' ),
							taxonomy.name
						);
					} else {
						blockName = __(
							'Total Taxonomy Terms',
							'gatherpress-statistics'
						);
					}
				} else {
					blockName = __(
						'Total Taxonomy Terms',
						'gatherpress-statistics'
					);
				}
				break;
			case 'taxonomy_terms_by_taxonomy':
				if ( countTaxonomy && filterTaxonomy && useContextTerm ) {
					const countTax = filteredTaxonomies?.find(
						( t ) => t.slug === countTaxonomy
					);
					const filterTax = filteredTaxonomies?.find(
						( t ) => t.slug === filterTaxonomy
					);
					blockName = sprintf(
						/* translators: 1: count taxonomy name, 2: filter taxonomy name */
						__(
							'%1$s in %2$s: current post\u2019s term',
							'gatherpress-statistics'
						),
						countTax ? countTax.name : countTaxonomy,
						filterTax ? filterTax.name : filterTaxonomy
					);
				} else if ( countTaxonomy && filterTaxonomy && selectedTerm ) {
					const countTax = filteredTaxonomies?.find(
						( t ) => t.slug === countTaxonomy
					);
					const filterTax = filteredTaxonomies?.find(
						( t ) => t.slug === filterTaxonomy
					);
					const term = allTaxonomyTerms[ filterTaxonomy ]?.find(
						( t ) => t.id === selectedTerm
					);
					if ( countTax && filterTax && term ) {
						blockName = sprintf(
							/* translators: 1: count taxonomy name, 2: filter taxonomy name, 3: term name */
							__(
								'%1$s in %2$s: %3$s',
								'gatherpress-statistics'
							),
							countTax.name,
							filterTax.name,
							term.name
						);
					} else {
						blockName = __(
							'Taxonomy Terms by Taxonomy',
							'gatherpress-statistics'
						);
					}
				} else {
					blockName = __(
						'Taxonomy Terms by Taxonomy',
						'gatherpress-statistics'
					);
				}
				break;
			default:
				blockName = __(
					'GatherPress Statistics',
					'gatherpress-statistics'
				);
		}

		// CRITICAL: For total_attendees, always add "Past:" prefix since it only shows past events
		if ( statisticType === 'total_attendees' || eventQuery === 'past' ) {
			blockName = sprintf(
				/* translators: %s: statistic name */
				__( 'Past: %s', 'gatherpress-statistics' ),
				blockName
			);
		} else if ( eventQuery === 'upcoming' ) {
			blockName = sprintf(
				/* translators: %s: statistic name */
				__( 'Upcoming: %s', 'gatherpress-statistics' ),
				blockName
			);
		}

		// Update the block's metadata name
		if ( blockName ) {
			updateBlockAttributes( clientId, {
				metadata: {
					name: blockName,
				},
			} );
		}
	}, [
		statisticType,
		selectedTaxonomyTerms,
		selectedTerm,
		selectedTaxonomy,
		countTaxonomy,
		filterTaxonomy,
		useContextTerm,
		contextTaxonomies,
		eventQuery,
		filteredTaxonomies,
		allTaxonomyTerms,
		clientId,
		updateBlockAttributes,
		labelPlural,
	] );

	// Build statistic type options - filter by what's supported
	const allStatisticTypeOptions = [
		{
			label: sprintf(
				/* translators: %s: plural post type label */
				__( 'Total %s', 'gatherpress-statistics' ),
				labelPlural
			),
			value: 'total_events',
		},
		{
			label: __( 'Total Attendees', 'gatherpress-statistics' ),
			value: 'total_attendees',
		},
		{
			label: sprintf(
				/* translators: %s: plural post type label */
				__( '%s per Taxonomy Term', 'gatherpress-statistics' ),
				labelPlural
			),
			value: 'events_per_taxonomy',
		},
		{
			label: sprintf(
				/* translators: %s: plural post type label */
				__( '%s (Multiple Taxonomies)', 'gatherpress-statistics' ),
				labelPlural
			),
			value: 'events_multi_taxonomy',
		},
		{
			label: __( 'Total Taxonomy Terms', 'gatherpress-statistics' ),
			value: 'total_taxonomy_terms',
		},
		{
			label: __(
				'Taxonomy Terms by Another Taxonomy',
				'gatherpress-statistics'
			),
			value: 'taxonomy_terms_by_taxonomy',
		},
	];

	// Filter options based on supported types
	const statisticTypeOptions = isLoadingTypes
		? allStatisticTypeOptions
		: allStatisticTypeOptions.filter( ( option ) =>
				supportedTypes.includes( option.value )
		  );

	// Check if current statistic type is supported
	const isCurrentTypeSupported = supportedTypes.includes( statisticType );

	const showSingleTaxonomyFilter = [
		'events_per_taxonomy',
		'total_attendees',
	].includes( statisticType );
	const showMultiTaxonomy = [ 'events_multi_taxonomy' ].includes(
		statisticType
	);
	const showTotalTaxonomyTerms = [ 'total_taxonomy_terms' ].includes(
		statisticType
	);
	const showTaxonomyTermsByTaxonomy = [
		'taxonomy_terms_by_taxonomy',
	].includes( statisticType );
	// CRITICAL: Event query filter should NOT be shown for total_attendees (always past)
	const showEventQueryFilter = ! [
		'total_taxonomy_terms',
		'taxonomy_terms_by_taxonomy',
		'total_attendees',
	].includes( statisticType );

	// Generate taxonomy options for dropdowns from filtered taxonomies
	const taxonomyOptions = filteredTaxonomies
		? filteredTaxonomies.map( ( taxonomy ) => ( {
				label: taxonomy.name,
				value: taxonomy.slug,
		  } ) )
		: [];

	// Get terms for selected taxonomy (single filter)
	const selectedTaxonomyTermOptions =
		selectedTaxonomy && allTaxonomyTerms[ selectedTaxonomy ]
			? allTaxonomyTerms[ selectedTaxonomy ].map( ( term ) => ( {
					label: term.name,
					value: term.id,
			  } ) )
			: [];

	// Calculate display values for preview
	const useConditional = previewCount > conditionalThreshold;
	const displayPrefix =
		useConditional && prefixConditional ? prefixConditional : prefixDefault;
	const displaySuffix =
		useConditional && suffixConditional ? suffixConditional : suffixDefault;
	const displayLabel = previewCount === 1 ? labelSingular : labelPlural;

	return (
		<>
			<InspectorControls>
				{ ! isLoadingTypes && ! isCurrentTypeSupported && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'This statistic type is currently disabled. Enable it in your theme or plugin to use this block.',
							'gatherpress-statistics'
						) }
					</Notice>
				) }

				<PanelBody
					title={ __(
						'Statistic Settings',
						'gatherpress-statistics'
					) }
				>
					<SelectControl
						label={ __(
							'Statistic Type',
							'gatherpress-statistics'
						) }
						value={ statisticType }
						options={ statisticTypeOptions }
						onChange={ ( value ) => {
							setAttributes( { statisticType: value } );
							// Reset filters when changing type
							setAttributes( {
								selectedTaxonomyTerms: {},
								selectedTerm: 0,
								selectedTaxonomy: '',
								countTaxonomy: '',
								filterTaxonomy: '',
								useContextTerm: false,
								contextTaxonomies: [],
							} );
							// CRITICAL: If switching to total_attendees, set eventQuery to 'past'
							if ( value === 'total_attendees' ) {
								setAttributes( { eventQuery: 'past' } );
							}
						} }
						disabled={ isLoadingTypes }
					/>

					{ showEventQueryFilter && (
						<ToggleControl
							label={ __(
								'Upcoming Events',
								'gatherpress-statistics'
							) }
							checked={ eventQuery === 'upcoming' }
							onChange={ ( value ) =>
								setAttributes( {
									eventQuery: value ? 'upcoming' : 'past',
								} )
							}
							help={
								eventQuery === 'upcoming'
									? sprintf(
											/* translators: %s: plural post type label */
											__(
												'Showing statistics for upcoming %s',
												'gatherpress-statistics'
											),
											labelPlural.toLowerCase()
									  )
									: sprintf(
											/* translators: %s: plural post type label */
											__(
												'Showing statistics for past %s',
												'gatherpress-statistics'
											),
											labelPlural.toLowerCase()
									  )
							}
						/>
					) }

					<ToggleControl
						label={ __( 'Show Label', 'gatherpress-statistics' ) }
						checked={ showLabel }
						onChange={ ( value ) =>
							setAttributes( { showLabel: value } )
						}
					/>

					{ showLabel && (
						<>
							<TextControl
								label={ __(
									'Label (Singular)',
									'gatherpress-statistics'
								) }
								value={ labelSingular }
								onChange={ ( value ) =>
									setAttributes( { labelSingular: value } )
								}
								help={ __(
									'Used when count is 1',
									'gatherpress-statistics'
								) }
							/>
							<TextControl
								label={ __(
									'Label (Plural)',
									'gatherpress-statistics'
								) }
								value={ labelPlural }
								onChange={ ( value ) =>
									setAttributes( { labelPlural: value } )
								}
								help={ __(
									'Used when count is greater than 1',
									'gatherpress-statistics'
								) }
							/>
						</>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Prefix & Suffix', 'gatherpress-statistics' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __(
							'Default Prefix',
							'gatherpress-statistics'
						) }
						value={ prefixDefault }
						onChange={ ( value ) =>
							setAttributes( { prefixDefault: value } )
						}
						placeholder={ __(
							'e.g., +',
							'gatherpress-statistics'
						) }
					/>

					<TextControl
						label={ __(
							'Default Suffix',
							'gatherpress-statistics'
						) }
						value={ suffixDefault }
						onChange={ ( value ) =>
							setAttributes( { suffixDefault: value } )
						}
						placeholder={ __(
							'e.g., total',
							'gatherpress-statistics'
						) }
					/>

					<hr />

					<NumberControl
						label={ __(
							'Conditional Threshold',
							'gatherpress-statistics'
						) }
						value={ conditionalThreshold }
						onChange={ ( value ) =>
							setAttributes( {
								conditionalThreshold:
									parseInt( value, 10 ) || 10,
							} )
						}
						min={ 1 }
						help={ __(
							'Use alternate prefix/suffix when count exceeds this value',
							'gatherpress-statistics'
						) }
					/>

					<TextControl
						label={ __(
							'Conditional Prefix',
							'gatherpress-statistics'
						) }
						value={ prefixConditional }
						onChange={ ( value ) =>
							setAttributes( { prefixConditional: value } )
						}
						placeholder={ __(
							'e.g., Over',
							'gatherpress-statistics'
						) }
						help={ __(
							'Used when count > threshold',
							'gatherpress-statistics'
						) }
					/>

					<TextControl
						label={ __(
							'Conditional Suffix',
							'gatherpress-statistics'
						) }
						value={ suffixConditional }
						onChange={ ( value ) =>
							setAttributes( { suffixConditional: value } )
						}
						placeholder={ __(
							'e.g., and counting!',
							'gatherpress-statistics'
						) }
						help={ __(
							'Used when count > threshold',
							'gatherpress-statistics'
						) }
					/>
				</PanelBody>

				{ showSingleTaxonomyFilter && isCurrentTypeSupported && (
					<PanelBody
						title={ __(
							'Taxonomy Filter',
							'gatherpress-statistics'
						) }
						initialOpen={ false }
					>
						{ isLoadingTaxonomies ? (
							<p>
								{ __(
									'Loading taxonomies…',
									'gatherpress-statistics'
								) }
							</p>
						) : filteredTaxonomies &&
						  filteredTaxonomies.length > 0 ? (
							<>
								<SelectControl
									label={ __(
										'Select Taxonomy',
										'gatherpress-statistics'
									) }
									value={ selectedTaxonomy }
									options={ [
										{
											label: __(
												'Select a taxonomy',
												'gatherpress-statistics'
											),
											value: '',
										},
										...taxonomyOptions,
									] }
									onChange={ ( value ) => {
										setAttributes( {
											selectedTaxonomy: value,
											selectedTerm: 0,
										} );
									} }
								/>
								{ selectedTaxonomy && (
									<ToggleControl
										label={ __(
											"Use current post's term",
											'gatherpress-statistics'
										) }
										checked={ useContextTerm }
										onChange={ ( value ) =>
											setAttributes( {
												useContextTerm: value,
											} )
										}
										help={ __(
											'Instead of a fixed term, take the term from the event or venue this block is placed on \u2014 lets the same block be reused across a template.',
											'gatherpress-statistics'
										) }
									/>
								) }
								{ selectedTaxonomy &&
									! useContextTerm &&
									allTaxonomyTerms[ selectedTaxonomy ] && (
										<FormTokenField
											label={ __(
												'Select Term',
												'gatherpress-statistics'
											) }
											value={
												selectedTerm
													? [
															allTaxonomyTerms[
																selectedTaxonomy
															].find(
																( t ) =>
																	t.id ===
																	selectedTerm
															)?.name,
													  ].filter( Boolean )
													: []
											}
											suggestions={ allTaxonomyTerms[
												selectedTaxonomy
											].map( ( term ) => term.name ) }
											onChange={ ( tokens ) => {
												if ( tokens.length > 0 ) {
													const term =
														allTaxonomyTerms[
															selectedTaxonomy
														].find(
															( t ) =>
																t.name ===
																tokens[ 0 ]
														);
													if ( term ) {
														setAttributes( {
															selectedTerm:
																term.id,
														} );
													}
												} else {
													setAttributes( {
														selectedTerm: 0,
													} );
												}
											} }
											maxLength={ 1 }
											help={ __(
												'Select one term to filter by',
												'gatherpress-statistics'
											) }
										/>
									) }
							</>
						) : (
							<p>
								{ __(
									'No taxonomies available',
									'gatherpress-statistics'
								) }
							</p>
						) }
					</PanelBody>
				) }

				{ showTotalTaxonomyTerms && isCurrentTypeSupported && (
					<PanelBody
						title={ __(
							'Taxonomy Selection',
							'gatherpress-statistics'
						) }
						initialOpen={ false }
					>
						{ isLoadingTaxonomies ? (
							<p>
								{ __(
									'Loading taxonomies…',
									'gatherpress-statistics'
								) }
							</p>
						) : filteredTaxonomies &&
						  filteredTaxonomies.length > 0 ? (
							<SelectControl
								label={ __(
									'Select Taxonomy',
									'gatherpress-statistics'
								) }
								value={ selectedTaxonomy }
								options={ [
									{
										label: __(
											'Select a taxonomy',
											'gatherpress-statistics'
										),
										value: '',
									},
									...taxonomyOptions,
								] }
								onChange={ ( value ) =>
									setAttributes( { selectedTaxonomy: value } )
								}
							/>
						) : (
							<p>
								{ __(
									'No taxonomies available',
									'gatherpress-statistics'
								) }
							</p>
						) }
					</PanelBody>
				) }

				{ showTaxonomyTermsByTaxonomy && isCurrentTypeSupported && (
					<PanelBody
						title={ __(
							'Taxonomy Configuration',
							'gatherpress-statistics'
						) }
						initialOpen={ false }
					>
						{ isLoadingTaxonomies ? (
							<p>
								{ __(
									'Loading taxonomies…',
									'gatherpress-statistics'
								) }
							</p>
						) : filteredTaxonomies &&
						  filteredTaxonomies.length > 0 ? (
							<>
								<SelectControl
									label={ __(
										'Count Terms From',
										'gatherpress-statistics'
									) }
									value={ countTaxonomy }
									options={ [
										{
											label: __(
												'Select taxonomy to count',
												'gatherpress-statistics'
											),
											value: '',
										},
										...taxonomyOptions,
									] }
									onChange={ ( value ) =>
										setAttributes( {
											countTaxonomy: value,
										} )
									}
									help={ __(
										'Which taxonomy terms should be counted?',
										'gatherpress-statistics'
									) }
								/>
								<SelectControl
									label={ __(
										'Filter By Taxonomy',
										'gatherpress-statistics'
									) }
									value={ filterTaxonomy }
									options={ [
										{
											label: __(
												'Select taxonomy to filter by',
												'gatherpress-statistics'
											),
											value: '',
										},
										...taxonomyOptions,
									] }
									onChange={ ( value ) => {
										setAttributes( {
											filterTaxonomy: value,
											selectedTerm: 0,
										} );
									} }
									help={ __(
										'Which taxonomy should be used to filter?',
										'gatherpress-statistics'
									) }
								/>
								{ filterTaxonomy && (
									<ToggleControl
										label={ __(
											"Use current post's term",
											'gatherpress-statistics'
										) }
										checked={ useContextTerm }
										onChange={ ( value ) =>
											setAttributes( {
												useContextTerm: value,
											} )
										}
										help={ __(
											'Instead of a fixed term, take the filter term from the event or venue this block is placed on.',
											'gatherpress-statistics'
										) }
									/>
								) }
								{ filterTaxonomy &&
									! useContextTerm &&
									allTaxonomyTerms[ filterTaxonomy ] && (
										<SelectControl
											label={ __(
												'Select Term',
												'gatherpress-statistics'
											) }
											value={ selectedTerm }
											options={ [
												{
													label: __(
														'Select a term',
														'gatherpress-statistics'
													),
													value: 0,
												},
												...allTaxonomyTerms[
													filterTaxonomy
												].map( ( term ) => ( {
													label: term.name,
													value: term.id,
												} ) ),
											] }
											onChange={ ( value ) =>
												setAttributes( {
													selectedTerm: parseInt(
														value,
														10
													),
												} )
											}
										/>
									) }
							</>
						) : (
							<p>
								{ __(
									'No taxonomies available',
									'gatherpress-statistics'
								) }
							</p>
						) }
					</PanelBody>
				) }

				{ showMultiTaxonomy &&
					isCurrentTypeSupported &&
					filteredTaxonomies &&
					filteredTaxonomies.length > 0 && (
						<>
							{ filteredTaxonomies.map( ( taxonomy ) => {
								const taxonomyTerms =
									allTaxonomyTerms[ taxonomy.slug ] || [];
								const suggestions = taxonomyTerms.reduce(
									( acc, term ) => {
										acc[ term.name ] = term;
										return acc;
									},
									{}
								);

								const selectedTermIds =
									selectedTaxonomyTerms[ taxonomy.slug ] ||
									[];
								const selectedNames = taxonomyTerms
									.filter( ( term ) =>
										selectedTermIds.includes( term.id )
									)
									.map( ( term ) => term.name );
								const isContextTaxonomy = (
									contextTaxonomies || []
								).includes( taxonomy.slug );

								return (
									<PanelBody
										key={ taxonomy.slug }
										title={ taxonomy.name }
										initialOpen={ false }
									>
										<ToggleControl
											label={ __(
												"Use current post's term",
												'gatherpress-statistics'
											) }
											checked={ isContextTaxonomy }
											onChange={ ( value ) => {
												const next = value
													? [
															...( contextTaxonomies ||
																[] ),
															taxonomy.slug,
													  ]
													: (
															contextTaxonomies ||
															[]
													  ).filter(
															( slug ) =>
																slug !==
																taxonomy.slug
													  );
												setAttributes( {
													contextTaxonomies: next,
												} );
											} }
											help={ __(
												'Take this taxonomy\u2019s term from the event or venue this block is placed on, instead of a fixed selection.',
												'gatherpress-statistics'
											) }
										/>
										{ ! isContextTaxonomy &&
											taxonomyTerms.length > 0 && (
												<FormTokenField
													label={ __(
														'Select Terms',
														'gatherpress-statistics'
													) }
													value={ selectedNames }
													suggestions={ Object.keys(
														suggestions
													) }
													onChange={ ( tokens ) => {
														const ids = tokens
															.map(
																( name ) =>
																	suggestions[
																		name
																	]?.id
															)
															.filter( Boolean );
														const newSelectedTaxonomyTerms =
															{
																...selectedTaxonomyTerms,
																[ taxonomy.slug ]:
																	ids,
															};
														setAttributes( {
															selectedTaxonomyTerms:
																newSelectedTaxonomyTerms,
														} );
													} }
												/>
											) }
										{ ! isContextTaxonomy &&
											taxonomyTerms.length === 0 && (
												<p>
													{ __(
														'No terms found',
														'gatherpress-statistics'
													) }
												</p>
											) }
									</PanelBody>
								);
							} ) }
						</>
					) }
			</InspectorControls>

			<div { ...useBlockProps() }>
				{ ! isLoadingTypes && ! isCurrentTypeSupported ? (
					<div
						className="gatherpress-stats-preview"
						style={ { opacity: 0.5 } }
					>
						<div className="gatherpress-stats-value">⚠️</div>
						<div className="gatherpress-stats-label">
							{ __(
								'Statistic type disabled',
								'gatherpress-statistics'
							) }
						</div>
					</div>
				) : (
					<div className="gatherpress-stats-preview">
						<div className="gatherpress-stats-value">
							{ displayPrefix && (
								<span className="gatherpress-stats-prefix">
									{ displayPrefix }
								</span>
							) }
							{ displayPrefix && ' ' }
							<span className="gatherpress-stats-number">
								{ previewCount }
							</span>
							{ displaySuffix && ' ' }
							{ displaySuffix && (
								<span className="gatherpress-stats-suffix">
									{ displaySuffix }
								</span>
							) }
						</div>
						{ showLabel && (
							<div className="gatherpress-stats-label">
								{ displayLabel }
							</div>
						) }
					</div>
				) }
			</div>
		</>
	);
}
