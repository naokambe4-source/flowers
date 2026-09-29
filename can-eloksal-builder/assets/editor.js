/**
 * Can Eloksal Builder — blok editörü.
 * Tüm bloklar PHP'deki tanımlardan (window.CEBlocks) otomatik oluşturulur:
 * sağ panelde ayarlar, sayfada sunucu tarafı canlı önizleme.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;
	var defs = window.CEBlocks || {};

	function imageControl( field, value, onChange ) {
		return el( be.MediaUploadCheck, { key: field.key },
			el( be.MediaUpload, {
				allowedTypes: [ 'image' ],
				value: value,
				onSelect: function ( media ) { onChange( media.id ); },
				render: function ( obj ) {
					return el( 'div', { className: 'ceb-image-control' },
						el( 'p', { className: 'ceb-image-control__label' }, field.label ),
						value ? el( ImagePreview, { id: value } ) : null,
						el( 'div', { className: 'ceb-image-control__actions' },
							el( c.Button, { variant: 'secondary', onClick: obj.open }, value ? 'Değiştir' : 'Görsel seç' ),
							value ? el( c.Button, { variant: 'link', isDestructive: true, onClick: function () { onChange( 0 ); } }, 'Kaldır' ) : null
						)
					);
				}
			} )
		);
	}

	function ImagePreview( props ) {
		var media = wp.data.useSelect( function ( select ) {
			return props.id ? select( 'core' ).getMedia( props.id ) : null;
		}, [ props.id ] );
		if ( ! media ) { return el( c.Spinner ); }
		var url = media.media_details && media.media_details.sizes && media.media_details.sizes.medium ? media.media_details.sizes.medium.source_url : media.source_url;
		return el( 'img', { src: url, alt: '', className: 'ceb-image-control__img' } );
	}

	function control( field, attributes, setAttributes ) {
		var value = attributes[ field.key ];
		var set = function ( v ) { var o = {}; o[ field.key ] = v; setAttributes( o ); };
		switch ( field.type ) {
			case 'textarea':
				return el( c.TextareaControl, { key: field.key, label: field.label, help: field.help, value: value || '', onChange: set, rows: 4 } );
			case 'lines':
				return el( c.TextareaControl, { key: field.key, label: field.label, help: field.help, value: value || '', onChange: set, rows: 6, className: 'ceb-lines' } );
			case 'select':
				return el( c.SelectControl, {
					key: field.key, label: field.label, value: value, onChange: set,
					options: Object.keys( field.options || {} ).map( function ( k ) { return { value: k, label: field.options[ k ] }; } )
				} );
			case 'toggle':
				return el( c.ToggleControl, { key: field.key, label: field.label, checked: !! value, onChange: set } );
			case 'number':
				return el( c.RangeControl, { key: field.key, label: field.label, value: value, onChange: set, min: field.range ? field.range[ 0 ] : 0, max: field.range ? field.range[ 1 ] : 100 } );
			case 'image':
				return imageControl( field, value, set );
			case 'url':
				return el( c.TextControl, { key: field.key, label: field.label, value: value || '', onChange: set, type: 'text', placeholder: 'https://… veya /sayfa/' } );
			default:
				return el( c.TextControl, { key: field.key, label: field.label, help: field.help, value: value || '', onChange: set } );
		}
	}

	Object.keys( defs ).forEach( function ( name ) {
		var def = defs[ name ];

		var edit = function ( props ) {
			var a = props.attributes;
			var sectionCls = 'ce-section ce-block-section ce-block-section--' + ( a.bg || 'light' ) + ( 'tint' === a.bg ? ' ce-section--tint' : '' ) + ( 'dark' === a.bg ? ' ce-section--dark' : '' ) + ( 'compact' === a.padding ? ' ce-section--compact' : '' );
			var blockProps = be.useBlockProps( def.inner
				? { className: sectionCls, style: 'none' === a.padding ? { paddingBlock: 0 } : {} }
				: { className: 'ceb-block ceb-block--' + name.replace( 'ce/', '' ) } );
			var inspector = def.fields.length ? el( be.InspectorControls, {},
				el( c.PanelBody, { title: 'Bölüm ayarları', initialOpen: true },
					el( 'p', { className: 'ceb-hint' }, 'Boş bırakılan alanlar Can Eloksal → Tema Ayarları\'ndaki değerleri kullanır.' ),
					def.fields.map( function ( f ) { return control( f, props.attributes, props.setAttributes ); } )
				)
			) : null;

			if ( def.inner ) {
				var innerProps = be.useInnerBlocksProps(
					{ className: 'ceb-section__inner' + ( 'full' === a.width ? '' : ' ce-container' ) + ( 'narrow' === a.width ? ' ce-container--narrow' : '' ) },
					{ template: [ [ 'ce/heading', {} ], [ 'core/paragraph', { placeholder: 'Metin yazın veya "/" ile blok ekleyin…' } ] ] }
				);
				return el( Fragment, {}, inspector, el( 'section', blockProps, el( 'div', innerProps ) ) );
			}

			return el( Fragment, {}, inspector,
				el( 'div', blockProps,
					el( c.Disabled, {},
						el( SSR, {
							block: name,
							attributes: props.attributes,
							LoadingResponsePlaceholder: function () { return el( 'div', { className: 'ceb-loading' }, el( c.Spinner ), def.title ); },
							EmptyResponsePlaceholder: function () { return el( 'div', { className: 'ceb-empty' }, def.title + ': gösterilecek içerik yok.' ); }
						} )
					)
				)
			);
		};

		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: def.title,
			description: def.description,
			category: 'can-eloksal',
			icon: def.icon,
			keywords: [ 'can eloksal', 'bölüm', 'section' ],
			supports: { html: false, customClassName: true },
			edit: edit,
			save: def.inner ? function () { return el( be.InnerBlocks.Content ); } : function () { return null; }
		} );
	} );
}( window.wp ) );
