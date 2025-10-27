( function( blocks, element, components, editor, apiFetch, i18n ) {
    'use strict';
    
    const { registerBlockType } = blocks;
    const { createElement: el } = element;
    const { 
        SelectControl, 
        CheckboxControl, 
        RangeControl, 
        PanelBody, 
        Spinner,
        __experimentalInputControl: InputControl
    } = components;
    const { InspectorControls } = editor;
    const { __ } = i18n;

    // Services Grid Block
    registerBlockType( 'devonebc/services-grid', {
        title: __( 'Services Grid', 'wp-service-portfolio-manager' ),
        description: __( 'Display your services in a customizable grid layout.', 'wp-service-portfolio-manager' ),
        icon: 'grid-view',
        category: 'devonebc-services',
        keywords: [
            __( 'services', 'wp-service-portfolio-manager' ),
            __( 'portfolio', 'wp-service-portfolio-manager' ),
            __( 'grid', 'wp-service-portfolio-manager' )
        ],
        attributes: {
            count: {
                type: 'number',
                default: 6
            },
            columns: {
                type: 'number',
                default: 3
            },
            category: {
                type: 'string',
                default: ''
            },
            taxonomy: {
                type: 'string',
                default: 'services_categories'
            },
            showExcerpt: {
                type: 'boolean',
                default: true
            },
            showDate: {
                type: 'boolean',
                default: false
            },
            imageSize: {
                type: 'string',
                default: 'medium'
            },
            orderby: {
                type: 'string',
                default: 'date'
            },
            order: {
                type: 'string',
                default: 'DESC'
            },
            className: {
                type: 'string',
                default: ''
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { 
                count, 
                columns, 
                category, 
                taxonomy, 
                showExcerpt, 
                showDate, 
                imageSize, 
                orderby, 
                order 
            } = attributes;

            // Safe data access with fallbacks
            const availableTaxonomies = window.devonebcServicesData && window.devonebcServicesData.taxonomies ? 
                Object.keys( window.devonebcServicesData.taxonomies ).map( function( key ) {
                    return {
                        value: key,
                        label: window.devonebcServicesData.taxonomies[ key ].label || key
                    };
                }) : [];

            const currentTaxonomyTerms = window.devonebcServicesData && 
                                       window.devonebcServicesData.taxonomies && 
                                       window.devonebcServicesData.taxonomies[ taxonomy ] ? 
                window.devonebcServicesData.taxonomies[ taxonomy ].terms : [];

            const orderbyOptions = [
                { value: 'date', label: __( 'Date', 'wp-service-portfolio-manager' ) },
                { value: 'title', label: __( 'Title', 'wp-service-portfolio-manager' ) },
                { value: 'rand', label: __( 'Random', 'wp-service-portfolio-manager' ) },
                { value: 'menu_order', label: __( 'Menu Order', 'wp-service-portfolio-manager' ) }
            ];

            const orderOptions = [
                { value: 'ASC', label: __( 'Ascending', 'wp-service-portfolio-manager' ) },
                { value: 'DESC', label: __( 'Descending', 'wp-service-portfolio-manager' ) }
            ];

            const imageSizeOptions = window.devonebcServicesData && window.devonebcServicesData.imageSizes ? 
                window.devonebcServicesData.imageSizes : [
                    { value: 'thumbnail', label: __( 'Thumbnail', 'wp-service-portfolio-manager' ) },
                    { value: 'medium', label: __( 'Medium', 'wp-service-portfolio-manager' ) },
                    { value: 'large', label: __( 'Large', 'wp-service-portfolio-manager' ) }
                ];

            return el( 'div', { 
                className: 'devonebc-services-grid-editor'
            },
                el( InspectorControls, null,
                    el( PanelBody, { 
                        title: __( 'Services Grid Settings', 'wp-service-portfolio-manager' ), 
                        initialOpen: true 
                    },
                        el( RangeControl, {
                            label: __( 'Number of Services', 'wp-service-portfolio-manager' ),
                            value: count,
                            onChange: function( value ) {
                                setAttributes( { 
                                    count: Math.min( Math.max( parseInt( value ) || 1, 1 ), 50 ) 
                                } );
                            },
                            min: 1,
                            max: 50,
                            step: 1
                        } ),
                        el( RangeControl, {
                            label: __( 'Columns', 'wp-service-portfolio-manager' ),
                            value: columns,
                            onChange: function( value ) {
                                setAttributes( { 
                                    columns: Math.min( Math.max( parseInt( value ) || 1, 1 ), 6 ) 
                                } );
                            },
                            min: 1,
                            max: 6,
                            step: 1
                        } ),
                        availableTaxonomies.length > 0 && el( SelectControl, {
                            label: __( 'Taxonomy', 'wp-service-portfolio-manager' ),
                            value: taxonomy,
                            options: availableTaxonomies,
                            onChange: function( value ) {
                                setAttributes( { 
                                    taxonomy: value,
                                    category: '' 
                                } );
                            }
                        } ),
                        currentTaxonomyTerms.length > 0 && el( SelectControl, {
                            label: __( 'Category', 'wp-service-portfolio-manager' ),
                            value: category,
                            options: [ 
                                { value: '', label: __( 'All Categories', 'wp-service-portfolio-manager' ) }
                            ].concat( currentTaxonomyTerms ),
                            onChange: function( value ) {
                                setAttributes( { category: value } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Image Size', 'wp-service-portfolio-manager' ),
                            value: imageSize,
                            options: imageSizeOptions,
                            onChange: function( value ) {
                                setAttributes( { imageSize: value } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Order By', 'wp-service-portfolio-manager' ),
                            value: orderby,
                            options: orderbyOptions,
                            onChange: function( value ) {
                                setAttributes( { orderby: value } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Order', 'wp-service-portfolio-manager' ),
                            value: order,
                            options: orderOptions,
                            onChange: function( value ) {
                                setAttributes( { order: value } );
                            }
                        } ),
                        el( CheckboxControl, {
                            label: __( 'Show Excerpt', 'wp-service-portfolio-manager' ),
                            checked: showExcerpt,
                            onChange: function( value ) {
                                setAttributes( { showExcerpt: value } );
                            }
                        } ),
                        el( CheckboxControl, {
                            label: __( 'Show Date', 'wp-service-portfolio-manager' ),
                            checked: showDate,
                            onChange: function( value ) {
                                setAttributes( { showDate: value } );
                            }
                        } )
                    )
                ),

                // Preview
                el( 'div', { className: 'devonebc-services-preview' },
                    el( 'h3', null, __( 'Services Grid Preview', 'wp-service-portfolio-manager' ) ),
                    el( 'p', null, 
                        count + ' ' + __( 'services', 'wp-service-portfolio-manager' ) + ' • ' + 
                        columns + ' ' + __( 'columns', 'wp-service-portfolio-manager' )
                    ),
                    category && el( 'p', null, 
                        __( 'Category:', 'wp-service-portfolio-manager' ) + ' ' + category 
                    ),
                    el( 'div', { 
                        style: { 
                            display: 'grid', 
                            gridTemplateColumns: 'repeat(' + columns + ', 1fr)',
                            gap: '15px',
                            marginTop: '15px'
                        } 
                    },
                        Array.from( { length: Math.min( count, 3 ) } ).map( function( _, index ) {
                            return el( 'div', {
                                key: index,
                                style: {
                                    background: '#f0f0f0',
                                    padding: '20px',
                                    borderRadius: '4px',
                                    textAlign: 'center'
                                }
                            }, 
                                el( 'div', { 
                                    className: 'service-image-placeholder',
                                    style: { fontSize: '24px', padding: '20px' } 
                                }, '📷' ),
                                el( 'p', { style: { margin: '10px 0 0 0', fontWeight: 'bold' } }, 
                                    __( 'Service', 'wp-service-portfolio-manager' ) + ' ' + ( index + 1 )
                                )
                            );
                        } )
                    )
                )
            );
        },

        save: function() {
            return null; // Server-side rendering
        }
    } );

    // Single Service Block
    registerBlockType( 'devonebc/single-service', {
        title: __( 'Single Service', 'wp-service-portfolio-manager' ),
        description: __( 'Display a single service with various layout options.', 'wp-service-portfolio-manager' ),
        icon: 'admin-page',
        category: 'devonebc-services',
        keywords: [
            __( 'service', 'wp-service-portfolio-manager' ),
            __( 'single', 'wp-service-portfolio-manager' ),
            __( 'portfolio', 'wp-service-portfolio-manager' )
        ],
        attributes: {
            serviceId: {
                type: 'number',
                default: 0
            },
            showImage: {
                type: 'boolean',
                default: true
            },
            showContent: {
                type: 'boolean',
                default: true
            },
            layout: {
                type: 'string',
                default: 'vertical'
            }
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { serviceId, showImage, showContent, layout } = attributes;

            const services = window.devonebcServicesData && window.devonebcServicesData.services ? 
                window.devonebcServicesData.services : [];

            const selectedService = services.find( function( service ) {
                return service.value === serviceId;
            } );

            const layoutOptions = [
                { value: 'vertical', label: __( 'Vertical', 'wp-service-portfolio-manager' ) },
                { value: 'horizontal', label: __( 'Horizontal', 'wp-service-portfolio-manager' ) }
            ];

            return el( 'div', { 
                className: 'devonebc-single-service-editor'
            },
                el( InspectorControls, null,
                    el( PanelBody, { 
                        title: __( 'Single Service Settings', 'wp-service-portfolio-manager' ), 
                        initialOpen: true 
                    },
                        el( SelectControl, {
                            label: __( 'Select Service', 'wp-service-portfolio-manager' ),
                            value: serviceId,
                            options: [ 
                                { value: 0, label: __( 'Select a service...', 'wp-service-portfolio-manager' ) } 
                            ].concat( services ),
                            onChange: function( value ) {
                                setAttributes( { serviceId: parseInt( value ) } );
                            }
                        } ),
                        el( SelectControl, {
                            label: __( 'Layout', 'wp-service-portfolio-manager' ),
                            value: layout,
                            options: layoutOptions,
                            onChange: function( value ) {
                                setAttributes( { layout: value } );
                            }
                        } ),
                        el( CheckboxControl, {
                            label: __( 'Show Image', 'wp-service-portfolio-manager' ),
                            checked: showImage,
                            onChange: function( value ) {
                                setAttributes( { showImage: value } );
                            }
                        } ),
                        el( CheckboxControl, {
                            label: __( 'Show Content', 'wp-service-portfolio-manager' ),
                            checked: showContent,
                            onChange: function( value ) {
                                setAttributes( { showContent: value } );
                            }
                        } )
                    )
                ),

                // Preview
                el( 'div', { className: 'devonebc-single-service-preview' },
                    serviceId && selectedService ? 
                        el( 'div', { 
                            className: 'layout-' + layout,
                            style: { 
                                display: layout === 'horizontal' ? 'grid' : 'block',
                                gridTemplateColumns: layout === 'horizontal' ? '1fr 2fr' : 'none',
                                gap: layout === 'horizontal' ? '20px' : '0'
                            } 
                        },
                            showImage && el( 'div', { 
                                className: 'service-image-placeholder'
                            }, '📷' ),
                            el( 'div', null,
                                el( 'h3', null, selectedService.label ),
                                showContent && el( 'p', null, 
                                    __( 'Service description and content will appear here.', 'wp-service-portfolio-manager' )
                                ),
                                el( 'a', { 
                                    href: '#',
                                    className: 'devonebc-service-link',
                                    style: { 
                                        display: 'inline-block', 
                                        marginTop: '10px',
                                        padding: '8px 16px',
                                        background: '#2271b1',
                                        color: '#fff',
                                        textDecoration: 'none',
                                        borderRadius: '4px'
                                    } 
                                }, __( 'Learn More', 'wp-service-portfolio-manager' ) )
                            )
                        ) :
                        el( 'p', null, __( 'Please select a service to display.', 'wp-service-portfolio-manager' ) )
                )
            );
        },

        save: function() {
            return null; // Server-side rendering
        }
    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.editor,
    window.wp.apiFetch,
    window.wp.i18n
);