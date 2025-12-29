/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"gatherpress/statistics","version":"0.1.0","title":"GatherPress Statistics","keywords":["statistics","counters","gatherpress","events"],"category":"widgets","icon":"chart-bar","description":"Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.","example":{"attributes":{"statisticType":"total_events","labelSingular":"Event","labelPlural":"Events","eventQuery":"past"}},"attributes":{"statisticType":{"type":"string","default":"total_events"},"labelSingular":{"type":"string","default":"Event"},"labelPlural":{"type":"string","default":"Events"},"selectedTaxonomyTerms":{"type":"object","default":{}},"selectedTerm":{"type":"number","default":0},"selectedTaxonomy":{"type":"string","default":""},"countTaxonomy":{"type":"string","default":""},"filterTaxonomy":{"type":"string","default":""},"eventQuery":{"type":"string","default":"past","enum":["upcoming","past"]},"showLabel":{"type":"boolean","default":true},"prefixDefault":{"type":"string","default":""},"suffixDefault":{"type":"string","default":""},"prefixConditional":{"type":"string","default":""},"suffixConditional":{"type":"string","default":""},"conditionalThreshold":{"type":"number","default":10}},"supports":{"html":false,"align":true,"color":{"background":true,"text":true,"gradients":true,"link":true},"spacing":{"padding":true,"margin":true,"blockGap":true},"typography":{"fontSize":true,"lineHeight":true,"__experimentalFontFamily":true,"__experimentalFontWeight":true,"__experimentalFontStyle":true,"__experimentalTextTransform":true,"__experimentalLetterSpacing":true,"__experimentalTextDecoration":true},"shadow":true,"__experimentalBorder":{"color":true,"radius":true,"style":true,"width":true,"__experimentalDefaultControls":{"color":true,"radius":true,"style":true,"width":true}}},"styles":[{"name":"default","label":"Counter","isDefault":true},{"name":"card","label":"Card"},{"name":"minimal","label":"Minimal"},{"name":"confetti","label":"Confetti"}],"textdomain":"gatherpress-statistics","editorScript":"file:./index.js","editorStyle":"file:./index.css","style":"file:./style-index.css","viewScript":"file:./view.js","render":"file:./render.php"}');

/***/ }),

/***/ "./src/edit.js":
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./editor.scss */ "./src/editor.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__);
/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */


/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */








/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 * @param          props.attributes
 * @param          props.setAttributes
 * @param          props.clientId
 *
 * @param {Object} props               Block properties.
 * @return {Element} Element to render.
 */

function Edit({
  attributes,
  setAttributes,
  clientId
}) {
  const {
    statisticType,
    labelSingular,
    labelPlural,
    selectedTaxonomyTerms,
    selectedTerm,
    selectedTaxonomy,
    countTaxonomy,
    filterTaxonomy,
    eventQuery,
    showLabel,
    prefixDefault,
    suffixDefault,
    prefixConditional,
    suffixConditional,
    conditionalThreshold
  } = attributes;
  const {
    updateBlockAttributes
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useDispatch)('core/block-editor');

  // Generate random preview count (between 1 and 100)
  const [previewCount] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(() => Math.floor(Math.random() * 100) + 1);

  // State for filtered taxonomies and supported types from REST API
  const [filteredTaxonomies, setFilteredTaxonomies] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)([]);
  const [supportedTypes, setSupportedTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)([]);
  const [isLoadingTaxonomies, setIsLoadingTaxonomies] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(true);
  const [isLoadingTypes, setIsLoadingTypes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(true);

  // Fetch filtered taxonomies from REST API
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    setIsLoadingTaxonomies(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/gatherpress-statistics/v1/taxonomies'
    }).then(taxonomies => {
      setFilteredTaxonomies(taxonomies);
      setIsLoadingTaxonomies(false);
    }).catch(() => {
      setFilteredTaxonomies([]);
      setIsLoadingTaxonomies(false);
    });
  }, []);

  // Fetch supported statistic types from REST API
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    setIsLoadingTypes(true);
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_6___default()({
      path: '/gatherpress-statistics/v1/supported-types'
    }).then(types => {
      setSupportedTypes(types);
      setIsLoadingTypes(false);
    }).catch(() => {
      setSupportedTypes([]);
      setIsLoadingTypes(false);
    });
  }, []);

  // CRITICAL: For total_attendees, always set eventQuery to 'past' internally
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    if (statisticType === 'total_attendees') {
      // Always ensure eventQuery is 'past' for total_attendees
      if (eventQuery !== 'past') {
        setAttributes({
          eventQuery: 'past'
        });
      }
    } else {
      // For other types, ensure eventQuery has a valid value
      if (!eventQuery || !['upcoming', 'past'].includes(eventQuery)) {
        setAttributes({
          eventQuery: 'past'
        });
      }
    }
  }, [statisticType, eventQuery, setAttributes]);

  // Fetch terms for all filtered taxonomies
  const allTaxonomyTerms = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.useSelect)(select => {
    if (!filteredTaxonomies || filteredTaxonomies.length === 0) {
      return {};
    }
    const {
      getEntityRecords
    } = select('core');
    const termsMap = {};
    filteredTaxonomies.forEach(taxonomy => {
      const terms = getEntityRecords('taxonomy', taxonomy.slug, {
        per_page: -1
      });
      if (terms) {
        termsMap[taxonomy.slug] = terms;
      }
    });
    return termsMap;
  }, [filteredTaxonomies]);

  // Update block name dynamically based on configuration
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useEffect)(() => {
    let blockName = '';

    // Build name based on statistic type
    switch (statisticType) {
      case 'total_events':
        blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total %s', 'gatherpress-statistics'), labelPlural);
        break;
      case 'total_attendees':
        blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total Attendees', 'gatherpress-statistics');
        break;
      case 'events_per_taxonomy':
        if (selectedTaxonomy && selectedTerm) {
          const taxonomy = filteredTaxonomies?.find(t => t.slug === selectedTaxonomy);
          const term = allTaxonomyTerms[selectedTaxonomy]?.find(t => t.id === selectedTerm);
          if (taxonomy && term) {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: 1: taxonomy name, 2: term name */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s: %2$s', 'gatherpress-statistics'), taxonomy.name, term.name);
          } else {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s per Taxonomy', 'gatherpress-statistics'), labelPlural);
          }
        } else {
          blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s per Taxonomy', 'gatherpress-statistics'), labelPlural);
        }
        break;
      case 'events_multi_taxonomy':
        if (selectedTaxonomyTerms && Object.keys(selectedTaxonomyTerms).length > 0) {
          const termNames = [];
          Object.entries(selectedTaxonomyTerms).forEach(([taxSlug, termIds]) => {
            if (termIds && termIds.length > 0) {
              const terms = allTaxonomyTerms[taxSlug] || [];
              termIds.forEach(termId => {
                const term = terms.find(t => t.id === termId);
                if (term) {
                  termNames.push(term.name);
                }
              });
            }
          });
          if (termNames.length > 0) {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: 1: plural post type label, 2: comma-separated list of terms */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s: %2$s', 'gatherpress-statistics'), labelPlural, termNames.join(', '));
          } else {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s (Multiple Taxonomies)', 'gatherpress-statistics'), labelPlural);
          }
        } else {
          blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s (Multiple Taxonomies)', 'gatherpress-statistics'), labelPlural);
        }
        break;
      case 'total_taxonomy_terms':
        if (selectedTaxonomy) {
          const taxonomy = filteredTaxonomies?.find(t => t.slug === selectedTaxonomy);
          if (taxonomy) {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: taxonomy name */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total %s', 'gatherpress-statistics'), taxonomy.name);
          } else {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total Taxonomy Terms', 'gatherpress-statistics');
          }
        } else {
          blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total Taxonomy Terms', 'gatherpress-statistics');
        }
        break;
      case 'taxonomy_terms_by_taxonomy':
        if (countTaxonomy && filterTaxonomy && selectedTerm) {
          const countTax = filteredTaxonomies?.find(t => t.slug === countTaxonomy);
          const filterTax = filteredTaxonomies?.find(t => t.slug === filterTaxonomy);
          const term = allTaxonomyTerms[filterTaxonomy]?.find(t => t.id === selectedTerm);
          if (countTax && filterTax && term) {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: 1: count taxonomy name, 2: filter taxonomy name, 3: term name */
            (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%1$s in %2$s: %3$s', 'gatherpress-statistics'), countTax.name, filterTax.name, term.name);
          } else {
            blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Terms by Taxonomy', 'gatherpress-statistics');
          }
        } else {
          blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Terms by Taxonomy', 'gatherpress-statistics');
        }
        break;
      default:
        blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('GatherPress Statistics', 'gatherpress-statistics');
    }

    // CRITICAL: For total_attendees, always add "Past:" prefix since it only shows past events
    if (statisticType === 'total_attendees') {
      blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: statistic name */
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Past: %s', 'gatherpress-statistics'), blockName);
    } else {
      // For other types, add event query type to name
      if (eventQuery === 'upcoming') {
        blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: statistic name */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Upcoming: %s', 'gatherpress-statistics'), blockName);
      } else if (eventQuery === 'past') {
        blockName = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: statistic name */
        (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Past: %s', 'gatherpress-statistics'), blockName);
      }
    }

    // Update the block's metadata name
    if (blockName) {
      updateBlockAttributes(clientId, {
        metadata: {
          name: blockName
        }
      });
    }
  }, [statisticType, selectedTaxonomyTerms, selectedTerm, selectedTaxonomy, countTaxonomy, filterTaxonomy, eventQuery, filteredTaxonomies, allTaxonomyTerms, clientId, updateBlockAttributes, labelPlural]);

  // Build statistic type options - filter by what's supported
  const allStatisticTypeOptions = [{
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total %s', 'gatherpress-statistics'), labelPlural),
    value: 'total_events'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total Attendees', 'gatherpress-statistics'),
    value: 'total_attendees'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s per Taxonomy Term', 'gatherpress-statistics'), labelPlural),
    value: 'events_per_taxonomy'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
    (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('%s (Multiple Taxonomies)', 'gatherpress-statistics'), labelPlural),
    value: 'events_multi_taxonomy'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Total Taxonomy Terms', 'gatherpress-statistics'),
    value: 'total_taxonomy_terms'
  }, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Terms by Another Taxonomy', 'gatherpress-statistics'),
    value: 'taxonomy_terms_by_taxonomy'
  }];

  // Filter options based on supported types
  const statisticTypeOptions = isLoadingTypes ? allStatisticTypeOptions : allStatisticTypeOptions.filter(option => supportedTypes.includes(option.value));

  // Check if current statistic type is supported
  const isCurrentTypeSupported = supportedTypes.includes(statisticType);
  const showSingleTaxonomyFilter = ['events_per_taxonomy', 'total_attendees'].includes(statisticType);
  const showMultiTaxonomy = ['events_multi_taxonomy'].includes(statisticType);
  const showTotalTaxonomyTerms = ['total_taxonomy_terms'].includes(statisticType);
  const showTaxonomyTermsByTaxonomy = ['taxonomy_terms_by_taxonomy'].includes(statisticType);
  // CRITICAL: Event query filter should NOT be shown for total_attendees (always past)
  const showEventQueryFilter = !['total_taxonomy_terms', 'taxonomy_terms_by_taxonomy', 'total_attendees'].includes(statisticType);

  // Generate taxonomy options for dropdowns from filtered taxonomies
  const taxonomyOptions = filteredTaxonomies ? filteredTaxonomies.map(taxonomy => ({
    label: taxonomy.name,
    value: taxonomy.slug
  })) : [];

  // Get terms for selected taxonomy (single filter)
  const selectedTaxonomyTermOptions = selectedTaxonomy && allTaxonomyTerms[selectedTaxonomy] ? allTaxonomyTerms[selectedTaxonomy].map(term => ({
    label: term.name,
    value: term.id
  })) : [];

  // Calculate display values for preview
  const useConditional = previewCount > conditionalThreshold;
  const displayPrefix = useConditional && prefixConditional ? prefixConditional : prefixDefault;
  const displaySuffix = useConditional && suffixConditional ? suffixConditional : suffixDefault;
  const displayLabel = previewCount === 1 ? labelSingular : labelPlural;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.InspectorControls, {
      children: [!isLoadingTypes && !isCurrentTypeSupported && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Notice, {
        status: "warning",
        isDismissible: false,
        children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('This statistic type is currently disabled. Enable it in your theme or plugin to use this block.', 'gatherpress-statistics')
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Statistic Settings', 'gatherpress-statistics'),
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Statistic Type', 'gatherpress-statistics'),
          value: statisticType,
          options: statisticTypeOptions,
          onChange: value => {
            setAttributes({
              statisticType: value
            });
            // Reset filters when changing type
            setAttributes({
              selectedTaxonomyTerms: {},
              selectedTerm: 0,
              selectedTaxonomy: '',
              countTaxonomy: '',
              filterTaxonomy: ''
            });
            // CRITICAL: If switching to total_attendees, set eventQuery to 'past'
            if (value === 'total_attendees') {
              setAttributes({
                eventQuery: 'past'
              });
            }
          },
          disabled: isLoadingTypes
        }), showEventQueryFilter && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Upcoming Events', 'gatherpress-statistics'),
          checked: eventQuery === 'upcoming',
          onChange: value => setAttributes({
            eventQuery: value ? 'upcoming' : 'past'
          }),
          help: eventQuery === 'upcoming' ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Showing statistics for upcoming %s', 'gatherpress-statistics'), labelPlural.toLowerCase()) : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: %s: plural post type label */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Showing statistics for past %s', 'gatherpress-statistics'), labelPlural.toLowerCase())
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Show Label', 'gatherpress-statistics'),
          checked: showLabel,
          onChange: value => setAttributes({
            showLabel: value
          })
        }), showLabel && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Label (Singular)', 'gatherpress-statistics'),
            value: labelSingular,
            onChange: value => setAttributes({
              labelSingular: value
            }),
            help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Used when count is 1', 'gatherpress-statistics')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Label (Plural)', 'gatherpress-statistics'),
            value: labelPlural,
            onChange: value => setAttributes({
              labelPlural: value
            }),
            help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Used when count is greater than 1', 'gatherpress-statistics')
          })]
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Prefix & Suffix', 'gatherpress-statistics'),
        initialOpen: false,
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Default Prefix', 'gatherpress-statistics'),
          value: prefixDefault,
          onChange: value => setAttributes({
            prefixDefault: value
          }),
          placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('e.g., +', 'gatherpress-statistics')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Default Suffix', 'gatherpress-statistics'),
          value: suffixDefault,
          onChange: value => setAttributes({
            suffixDefault: value
          }),
          placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('e.g., total', 'gatherpress-statistics')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("hr", {}), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalNumberControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Conditional Threshold', 'gatherpress-statistics'),
          value: conditionalThreshold,
          onChange: value => setAttributes({
            conditionalThreshold: parseInt(value, 10) || 10
          }),
          min: 1,
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Use alternate prefix/suffix when count exceeds this value', 'gatherpress-statistics')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Conditional Prefix', 'gatherpress-statistics'),
          value: prefixConditional,
          onChange: value => setAttributes({
            prefixConditional: value
          }),
          placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('e.g., Over', 'gatherpress-statistics'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Used when count > threshold', 'gatherpress-statistics')
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.TextControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Conditional Suffix', 'gatherpress-statistics'),
          value: suffixConditional,
          onChange: value => setAttributes({
            suffixConditional: value
          }),
          placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('e.g., and counting!', 'gatherpress-statistics'),
          help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Used when count > threshold', 'gatherpress-statistics')
        })]
      }), showSingleTaxonomyFilter && isCurrentTypeSupported && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Filter', 'gatherpress-statistics'),
        initialOpen: false,
        children: isLoadingTaxonomies ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading taxonomies…', 'gatherpress-statistics')
        }) : filteredTaxonomies && filteredTaxonomies.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Taxonomy', 'gatherpress-statistics'),
            value: selectedTaxonomy,
            options: [{
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select a taxonomy', 'gatherpress-statistics'),
              value: ''
            }, ...taxonomyOptions],
            onChange: value => {
              setAttributes({
                selectedTaxonomy: value,
                selectedTerm: 0
              });
            }
          }), selectedTaxonomy && allTaxonomyTerms[selectedTaxonomy] && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FormTokenField, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Term', 'gatherpress-statistics'),
            value: selectedTerm ? [allTaxonomyTerms[selectedTaxonomy].find(t => t.id === selectedTerm)?.name].filter(Boolean) : [],
            suggestions: allTaxonomyTerms[selectedTaxonomy].map(term => term.name),
            onChange: tokens => {
              if (tokens.length > 0) {
                const term = allTaxonomyTerms[selectedTaxonomy].find(t => t.name === tokens[0]);
                if (term) {
                  setAttributes({
                    selectedTerm: term.id
                  });
                }
              } else {
                setAttributes({
                  selectedTerm: 0
                });
              }
            },
            maxLength: 1,
            help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select one term to filter by', 'gatherpress-statistics')
          })]
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No taxonomies available', 'gatherpress-statistics')
        })
      }), showTotalTaxonomyTerms && isCurrentTypeSupported && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Selection', 'gatherpress-statistics'),
        initialOpen: false,
        children: isLoadingTaxonomies ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading taxonomies…', 'gatherpress-statistics')
        }) : filteredTaxonomies && filteredTaxonomies.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Taxonomy', 'gatherpress-statistics'),
          value: selectedTaxonomy,
          options: [{
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select a taxonomy', 'gatherpress-statistics'),
            value: ''
          }, ...taxonomyOptions],
          onChange: value => setAttributes({
            selectedTaxonomy: value
          })
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No taxonomies available', 'gatherpress-statistics')
        })
      }), showTaxonomyTermsByTaxonomy && isCurrentTypeSupported && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Taxonomy Configuration', 'gatherpress-statistics'),
        initialOpen: false,
        children: isLoadingTaxonomies ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading taxonomies…', 'gatherpress-statistics')
        }) : filteredTaxonomies && filteredTaxonomies.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Count Terms From', 'gatherpress-statistics'),
            value: countTaxonomy,
            options: [{
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select taxonomy to count', 'gatherpress-statistics'),
              value: ''
            }, ...taxonomyOptions],
            onChange: value => setAttributes({
              countTaxonomy: value
            }),
            help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Which taxonomy terms should be counted?', 'gatherpress-statistics')
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Filter By Taxonomy', 'gatherpress-statistics'),
            value: filterTaxonomy,
            options: [{
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select taxonomy to filter by', 'gatherpress-statistics'),
              value: ''
            }, ...taxonomyOptions],
            onChange: value => {
              setAttributes({
                filterTaxonomy: value,
                selectedTerm: 0
              });
            },
            help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Which taxonomy should be used to filter?', 'gatherpress-statistics')
          }), filterTaxonomy && allTaxonomyTerms[filterTaxonomy] && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Term', 'gatherpress-statistics'),
            value: selectedTerm,
            options: [{
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select a term', 'gatherpress-statistics'),
              value: 0
            }, ...allTaxonomyTerms[filterTaxonomy].map(term => ({
              label: term.name,
              value: term.id
            }))],
            onChange: value => setAttributes({
              selectedTerm: parseInt(value, 10)
            })
          })]
        }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No taxonomies available', 'gatherpress-statistics')
        })
      }), showMultiTaxonomy && isCurrentTypeSupported && filteredTaxonomies && filteredTaxonomies.length > 0 && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.Fragment, {
        children: filteredTaxonomies.map(taxonomy => {
          const taxonomyTerms = allTaxonomyTerms[taxonomy.slug] || [];
          const suggestions = taxonomyTerms.reduce((acc, term) => {
            acc[term.name] = term;
            return acc;
          }, {});
          const selectedTermIds = selectedTaxonomyTerms[taxonomy.slug] || [];
          const selectedNames = taxonomyTerms.filter(term => selectedTermIds.includes(term.id)).map(term => term.name);
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.PanelBody, {
            title: taxonomy.name,
            initialOpen: false,
            children: taxonomyTerms.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.FormTokenField, {
              label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Select Terms', 'gatherpress-statistics'),
              value: selectedNames,
              suggestions: Object.keys(suggestions),
              onChange: tokens => {
                const ids = tokens.map(name => suggestions[name]?.id).filter(Boolean);
                const newSelectedTaxonomyTerms = {
                  ...selectedTaxonomyTerms,
                  [taxonomy.slug]: ids
                };
                setAttributes({
                  selectedTaxonomyTerms: newSelectedTaxonomyTerms
                });
              }
            }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("p", {
              children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('No terms found', 'gatherpress-statistics')
            })
          }, taxonomy.slug);
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)(),
      children: !isLoadingTypes && !isCurrentTypeSupported ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
        className: "gatherpress-stats-preview",
        style: {
          opacity: 0.5
        },
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          className: "gatherpress-stats-value",
          children: "\u26A0\uFE0F"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          className: "gatherpress-stats-label",
          children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Statistic type disabled', 'gatherpress-statistics')
        })]
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
        className: "gatherpress-stats-preview",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsxs)("div", {
          className: "gatherpress-stats-value",
          children: [displayPrefix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("span", {
            className: "gatherpress-stats-prefix",
            children: displayPrefix
          }), displayPrefix && ' ', /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("span", {
            className: "gatherpress-stats-number",
            children: previewCount
          }), displaySuffix && ' ', displaySuffix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("span", {
            className: "gatherpress-stats-suffix",
            children: displaySuffix
          })]
        }), showLabel && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_8__.jsx)("div", {
          className: "gatherpress-stats-label",
          children: displayLabel
        })]
      })
    })]
  });
}

/***/ }),

/***/ "./src/editor.scss":
/*!*************************!*\
  !*** ./src/editor.scss ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./style.scss */ "./src/style.scss");
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./edit */ "./src/edit.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/block.json");
/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */


/**
 * Internal dependencies
 */



/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  edit: _edit__WEBPACK_IMPORTED_MODULE_2__["default"]
});

/***/ }),

/***/ "./src/style.scss":
/*!************************!*\
  !*** ./src/style.scss ***!
  \************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"index": 0,
/******/ 			"./style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkgatherpress_statistics"] = globalThis["webpackChunkgatherpress_statistics"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["./style-index"], () => (__webpack_require__("./src/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map