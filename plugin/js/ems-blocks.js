/**
 * Conference Starter Theme - EMS Gutenberg Blocks
 *
 * Provides visual block editor components for Event Management System shortcodes.
 * All blocks render their respective shortcodes on the frontend.
 *
 * @package    Conference_Starter
 * @subpackage JavaScript
 * @since      1.1.0
 * @version    1.1.0
 */

( function( wp ) {
    'use strict';

    // Destructure WordPress packages
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl } = wp.components;
    const { __ } = wp.i18n;

    // Get localized data
    const { events, i18n } = window.emsBlockData || { events: [], i18n: {} };

    // Build event options for select controls
    const eventOptions = [
        { label: i18n.selectEvent || 'Select Event', value: '' },
        ...events.map( event => ( {
            label: event.title,
            value: event.id.toString(),
        } ) ),
    ];

    // =========================================================================
    // Helper: Block Preview Component
    // =========================================================================
    
    const BlockPreview = ( { icon, title, description } ) => {
        return el( 'div', {
            className: 'ems-block-preview',
            style: {
                padding: '30px 20px',
                backgroundColor: '#f9f9f9',
                border: '1px solid #dce0e0',
                borderRadius: '5px',
                textAlign: 'center',
            },
        },
            el( 'span', {
                className: 'dashicons dashicons-' + icon,
                style: {
                    fontSize: '32px',
                    color: '#1EC6B6',
                    marginBottom: '10px',
                    display: 'block',
                },
            } ),
            el( 'strong', {
                style: {
                    display: 'block',
                    marginBottom: '5px',
                    fontSize: '14px',
                },
            }, title ),
            el( 'small', {
                style: {
                    color: '#666',
                },
            }, description )
        );
    };

    // =========================================================================
    // Block: Event List
    // =========================================================================
    
    registerBlockType( 'ems/event-list', {
        title: i18n.eventList || 'Event List',
        icon: 'calendar-alt',
        category: 'ems-blocks',
        description: __( 'Display a list of upcoming events', 'conference-starter' ),
        keywords: [ 'events', 'list', 'calendar' ],
        attributes: {
            limit: {
                type: 'number',
                default: 6,
            },
            showPast: {
                type: 'boolean',
                default: false,
            },
            columns: {
                type: 'number',
                default: 3,
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { limit, showPast, columns } = attributes;
            const blockProps = useBlockProps();

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( RangeControl, {
                            label: __( 'Number of Events', 'conference-starter' ),
                            value: limit,
                            onChange: ( value ) => setAttributes( { limit: value } ),
                            min: 1,
                            max: 24,
                        } ),
                        el( RangeControl, {
                            label: __( 'Columns', 'conference-starter' ),
                            value: columns,
                            onChange: ( value ) => setAttributes( { columns: value } ),
                            min: 1,
                            max: 4,
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Past Events', 'conference-starter' ),
                            checked: showPast,
                            onChange: ( value ) => setAttributes( { showPast: value } ),
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'calendar-alt',
                        title: i18n.eventList || 'Event List',
                        description: limit + ' ' + __( 'events in', 'conference-starter' ) + ' ' + columns + ' ' + __( 'columns', 'conference-starter' ),
                    } )
                )
            );
        },

        save: function( props ) {
            const { limit, showPast, columns } = props.attributes;
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: Event Schedule
    // =========================================================================
    
    registerBlockType( 'ems/event-schedule', {
        title: i18n.eventSchedule || 'Event Schedule',
        icon: 'list-view',
        category: 'ems-blocks',
        description: __( 'Display the schedule for an event', 'conference-starter' ),
        keywords: [ 'schedule', 'sessions', 'timetable' ],
        attributes: {
            eventId: {
                type: 'string',
                default: '',
            },
            view: {
                type: 'string',
                default: 'list',
            },
            groupBy: {
                type: 'string',
                default: 'date',
            },
            showFilters: {
                type: 'boolean',
                default: true,
            },
            showSearch: {
                type: 'boolean',
                default: true,
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { eventId, view, groupBy, showFilters, showSearch } = attributes;
            const blockProps = useBlockProps();

            const selectedEvent = events.find( e => e.id.toString() === eventId );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Event', 'conference-starter' ),
                            value: eventId,
                            options: eventOptions,
                            onChange: ( value ) => setAttributes( { eventId: value } ),
                        } ),
                        el( SelectControl, {
                            label: __( 'View', 'conference-starter' ),
                            value: view,
                            options: [
                                { label: __( 'List', 'conference-starter' ), value: 'list' },
                                { label: __( 'Grid', 'conference-starter' ), value: 'grid' },
                                { label: __( 'Timeline', 'conference-starter' ), value: 'timeline' },
                            ],
                            onChange: ( value ) => setAttributes( { view: value } ),
                        } ),
                        el( SelectControl, {
                            label: __( 'Group By', 'conference-starter' ),
                            value: groupBy,
                            options: [
                                { label: __( 'Date', 'conference-starter' ), value: 'date' },
                                { label: __( 'Track', 'conference-starter' ), value: 'track' },
                                { label: __( 'Location', 'conference-starter' ), value: 'location' },
                            ],
                            onChange: ( value ) => setAttributes( { groupBy: value } ),
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Filters', 'conference-starter' ),
                            checked: showFilters,
                            onChange: ( value ) => setAttributes( { showFilters: value } ),
                        } ),
                        el( ToggleControl, {
                            label: __( 'Show Search', 'conference-starter' ),
                            checked: showSearch,
                            onChange: ( value ) => setAttributes( { showSearch: value } ),
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'list-view',
                        title: i18n.eventSchedule || 'Event Schedule',
                        description: selectedEvent ? selectedEvent.title : i18n.selectEvent || 'Select an event',
                    } )
                )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: Registration Form
    // =========================================================================
    
    registerBlockType( 'ems/registration-form', {
        title: i18n.registrationForm || 'Registration Form',
        icon: 'clipboard',
        category: 'ems-blocks',
        description: __( 'Display the event registration form', 'conference-starter' ),
        keywords: [ 'registration', 'form', 'signup' ],
        attributes: {
            eventId: {
                type: 'string',
                default: '',
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { eventId } = attributes;
            const blockProps = useBlockProps();

            const selectedEvent = events.find( e => e.id.toString() === eventId );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Event', 'conference-starter' ),
                            value: eventId,
                            options: eventOptions,
                            onChange: ( value ) => setAttributes( { eventId: value } ),
                            help: __( 'Leave empty to auto-detect from page context', 'conference-starter' ),
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'clipboard',
                        title: i18n.registrationForm || 'Registration Form',
                        description: selectedEvent ? selectedEvent.title : __( 'Auto-detect event', 'conference-starter' ),
                    } )
                )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: Abstract Submission
    // =========================================================================
    
    registerBlockType( 'ems/abstract-submission', {
        title: i18n.abstractForm || 'Abstract Submission',
        icon: 'media-document',
        category: 'ems-blocks',
        description: __( 'Display the abstract submission form', 'conference-starter' ),
        keywords: [ 'abstract', 'submission', 'form', 'paper' ],
        attributes: {
            eventId: {
                type: 'string',
                default: '',
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { eventId } = attributes;
            const blockProps = useBlockProps();

            const selectedEvent = events.find( e => e.id.toString() === eventId );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Event', 'conference-starter' ),
                            value: eventId,
                            options: eventOptions,
                            onChange: ( value ) => setAttributes( { eventId: value } ),
                            help: __( 'Leave empty to show events accepting submissions', 'conference-starter' ),
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'media-document',
                        title: i18n.abstractForm || 'Abstract Submission',
                        description: selectedEvent ? selectedEvent.title : __( 'All open events', 'conference-starter' ),
                    } )
                )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: My Schedule
    // =========================================================================
    
    registerBlockType( 'ems/my-schedule', {
        title: i18n.mySchedule || 'My Schedule',
        icon: 'schedule',
        category: 'ems-blocks',
        description: __( 'Display the logged-in user\'s registered sessions', 'conference-starter' ),
        keywords: [ 'schedule', 'personal', 'sessions', 'my' ],
        attributes: {
            eventId: {
                type: 'string',
                default: '',
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { eventId } = attributes;
            const blockProps = useBlockProps();

            const selectedEvent = events.find( e => e.id.toString() === eventId );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Event', 'conference-starter' ),
                            value: eventId,
                            options: eventOptions,
                            onChange: ( value ) => setAttributes( { eventId: value } ),
                            help: __( 'Leave empty to show all registered events', 'conference-starter' ),
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'schedule',
                        title: i18n.mySchedule || 'My Schedule',
                        description: selectedEvent ? selectedEvent.title : __( 'All events', 'conference-starter' ),
                    } )
                )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: My Registrations
    // =========================================================================
    
    registerBlockType( 'ems/my-registrations', {
        title: i18n.myRegistrations || 'My Registrations',
        icon: 'admin-users',
        category: 'ems-blocks',
        description: __( 'Display the logged-in user\'s event registrations', 'conference-starter' ),
        keywords: [ 'registrations', 'my', 'events', 'personal' ],
        attributes: {},

        edit: function( props ) {
            const blockProps = useBlockProps();

            return el( 'div', blockProps,
                el( BlockPreview, {
                    icon: 'admin-users',
                    title: i18n.myRegistrations || 'My Registrations',
                    description: __( 'User\'s registered events', 'conference-starter' ),
                } )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: Presenter Dashboard
    // =========================================================================
    
    registerBlockType( 'ems/presenter-dashboard', {
        title: i18n.presenterDash || 'Presenter Dashboard',
        icon: 'microphone',
        category: 'ems-blocks',
        description: __( 'Display the presenter dashboard for abstract management', 'conference-starter' ),
        keywords: [ 'presenter', 'dashboard', 'abstracts', 'speaker' ],
        attributes: {},

        edit: function( props ) {
            const blockProps = useBlockProps();

            return el( 'div', blockProps,
                el( BlockPreview, {
                    icon: 'microphone',
                    title: i18n.presenterDash || 'Presenter Dashboard',
                    description: __( 'Manage submitted abstracts', 'conference-starter' ),
                } )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // =========================================================================
    // Block: Upcoming Events
    // =========================================================================
    
    registerBlockType( 'ems/upcoming-events', {
        title: __( 'Upcoming Events', 'conference-starter' ),
        icon: 'calendar',
        category: 'ems-blocks',
        description: __( 'Display upcoming events in a compact format', 'conference-starter' ),
        keywords: [ 'events', 'upcoming', 'next', 'soon' ],
        attributes: {
            limit: {
                type: 'number',
                default: 3,
            },
        },

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { limit } = attributes;
            const blockProps = useBlockProps();

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, { title: __( 'Settings', 'conference-starter' ), initialOpen: true },
                        el( RangeControl, {
                            label: __( 'Number of Events', 'conference-starter' ),
                            value: limit,
                            onChange: ( value ) => setAttributes( { limit: value } ),
                            min: 1,
                            max: 10,
                        } )
                    )
                ),
                el( 'div', blockProps,
                    el( BlockPreview, {
                        icon: 'calendar',
                        title: __( 'Upcoming Events', 'conference-starter' ),
                        description: limit + ' ' + __( 'events', 'conference-starter' ),
                    } )
                )
            );
        },

        save: function() {
            return null; // Server-side rendered
        },
    } );

    // Log successful registration
    console.log( 'EMS Gutenberg blocks registered successfully' );

} )( window.wp );
