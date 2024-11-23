(function ($) {

	'use strict';

	var BBElementorSectionsData = window.BBElementorSectionsData || {},
		BBElementorSectionsEditor,
		BBElementorSectionsEditorViews;

	BBElementorSectionsEditorViews = {

		ModalLayoutView: null,
		ModalHeaderView: null,
		ModalLoadingView: null,
		ModalBodyView: null,
		ModalErrorView: null,
		LibraryCollection: null,
		ModalCollectionView: null,
		ModalTabsCollection: null,
		ModalTabsCollectionView: null,
		FiltersCollectionView: null,
		FiltersItemView: null,
		ModalTabsItemView: null,
		ModalTemplateItemView: null,
		ModalInsertTemplateBehavior: null,
		ModalTemplateModel: null,
		CategoriesCollection: null,
		ModalHeaderLogo: null,
		TabModel: null,
		CategoryModel: null,
		TemplatesEmptyView: null,
		TemplateSearchCollectionView: null,

		init: function () {
			var self = this;

			self.ModalTemplateModel = Backbone.Model.extend({
				defaults: {
					template_id: 0,
					title: '',
					thumbnail: '',
					source: '',
					categories: []
				}
			});

			self.ModalHeaderView = Marionette.LayoutView.extend({

				id: 'bbelementor-template-modal-header',
				template: '#tmpl-bbelementor-template-modal-header',

				ui: {
					closeModal: '#bbelementor-template-modal-header-close-modal'
				},

				events: {
					'click @ui.closeModal': 'onCloseModalClick'
				},

				regions: {
					headerLogo: '#bbelementor-template-modal-header-logo-area',
					headerTabs: '#bbelementor-template-modal-header-tabs',
					headerActions: '#bbelementor-template-modal-header-actions'
				},

				onCloseModalClick: function () {
					BBElementorSectionsEditor.closeModal();
				}

			});

			self.TabModel = Backbone.Model.extend({
				defaults: {
					slug: '',
					title: ''
				}
			});

			self.LibraryCollection = Backbone.Collection.extend({
				model: self.ModalTemplateModel
			});

			self.ModalTabsCollection = Backbone.Collection.extend({
				model: self.TabModel
			});

			self.CategoryModel = Backbone.Model.extend({
				defaults: {
					slug: '',
					title: ''
				}
			});

			self.CategoriesCollection = Backbone.Collection.extend({
				model: self.CategoryModel
			});

			self.ModalHeaderLogo = Marionette.ItemView.extend({

				template: '#tmpl-bbelementor-template-modal-header-logo',

				id: 'bbelementor-template-modal-header-logo'

			});

			self.ModalBodyView = Marionette.LayoutView.extend({

				id: 'bbelementor-template-library-content',

				className: function () {
					return 'library-tab-' + BBElementorSectionsEditor.getTab();
				},

				template: '#tmpl-bbelementor-template-modal-content',

				regions: {
					contentTemplates: '.bbelementor-templates-list',
					contentFilters: '.bbelementor-filters-list',
					contentSearch: '#elementor-template-library-filter-text-wrapper',
				}

			});

			self.TemplatesEmptyView = Marionette.LayoutView.extend({

				id: 'bbelementor-template-modal-empty',

				template: '#tmpl-bbelementor-template-modal-empty',

				ui: {
					title: '.elementor-template-library-blank-title',
				},

				regions: {
					contentTemplates: '.bbelementor-templates-list',
					contentFilters: '.bbelementor-filters-list',
					contentSearch: '#elementor-template-library-filter-text-wrapper',
				}

			});

			self.ModalInsertTemplateBehavior = Marionette.Behavior.extend({
				ui: {
					insertButton: '.bbelementor-template-insert'
				},

				events: {
					'click @ui.insertButton': 'onInsertButtonClick'
				},

				onInsertButtonClick: function () {

					var templateModel = this.view.model,
						options = {};

					BBElementorSectionsEditor.layout.showLoadingView();
					$.ajax({
						url: ajaxurl,
						type: 'post',
						dataType: 'json',
						data: {
							action: 'bb_elementor_sections_inner_template',
							template: templateModel.attributes,
							tab: BBElementorSectionsEditor.getTab()
						}
					});

					elementor.templates.requestTemplateContent(
						templateModel.get('source'),
						templateModel.get('template_id'),
						{
							data: {
								tab: BBElementorSectionsEditor.getTab(),
								page_settings: false
							},
							success: function (data) {

								console.log("%c Template Inserted Successfully!!", "color: #7a7a7a; background-color: #eee;");

								BBElementorSectionsEditor.closeModal();

								elementor.channels.data.trigger('template:before:insert', templateModel);

								if (null !== BBElementorSectionsEditor.atIndex) {
									options.at = BBElementorSectionsEditor.atIndex;
								}

								elementor.previewView.addChildModel(data.content, options);

								elementor.channels.data.trigger('template:after:insert', templateModel);

								BBElementorSectionsEditor.atIndex = null;
								jQuery('.elementor-button-success').removeClass('elementor-disabled');
							},
							error: function (err) {
								BBElementorSectionsEditor.closeModal();
							}
						}
					);
				}
			});

			self.FiltersItemView = Marionette.ItemView.extend({

				template: '#tmpl-bbelementor-template-modal-filters-item',

				className: function () {
					return 'bbelementor-template-filter-item';
				},

				ui: function () {
					return {
						filterLabels: '.bbelementor-template-filter-label'
					};
				},

				events: function () {
					return {
						'click @ui.filterLabels': 'onFilterClick'
					};
				},

				onFilterClick: function (event) {

					var $clickedInput = jQuery(event.target);
					BBElementorSectionsEditor.setFilter('category', $clickedInput.val());
				}

			});

			self.TemplateSearchCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-bbelementor-template-modal-search-item',
				id: 'bbelementor-template-modal-search-item',

				ui: function () {
					return {
						textFilter: '#elementor-template-library-filter-text',
					};
				},

				events: function () {
					return {
						'input @ui.textFilter': 'onTextFilterInput',
					};
				},

				onTextFilterInput: function onTextFilterInput( childModel ) {

					var searchText = this.ui.textFilter.val();

					BBElementorSectionsEditor.setFilter('text', searchText);
				},

			});

			self.ModalTabsItemView = Marionette.ItemView.extend({

				template: '#tmpl-bbelementor-template-modal-tabs-item',

				className: function () {
					return 'elementor-template-library-menu-item';
				},

				ui: function () {
					return {
						tabsLabels: 'label',
						tabsInput: 'input'
					};
				},

				events: function () {
					return {
						'click @ui.tabsLabels': 'onTabClick'
					};
				},

				onRender: function () {
					if (this.model.get('slug') === BBElementorSectionsEditor.getTab()) {
						this.ui.tabsInput.attr('checked', 'checked');
					}
				},

				onTabClick: function (event) {

					var $clickedInput = jQuery(event.target);
					BBElementorSectionsEditor.setTab($clickedInput.val());
				}

			});

			self.FiltersCollectionView = Marionette.CompositeView.extend({

				id: 'bbelementor-template-library-filters',

				template: '#tmpl-bbelementor-template-modal-filters',

				childViewContainer: '#bbelementor-modal-filters-container',

				getChildView: function (childModel) {
					return self.FiltersItemView;
				}

			});

			self.ModalTabsCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-bbelementor-template-modal-tabs',

				childViewContainer: '#bbelementor-modal-tabs-items',

				initialize: function () {
					this.listenTo(BBElementorSectionsEditor.channels.layout, 'tamplate:cloned', this._renderChildren);
				},

				getChildView: function (childModel) {
					return self.ModalTabsItemView;
				}

			});

			self.ModalTemplateItemView = Marionette.ItemView.extend({

				template: '#tmpl-bbelementor-template-modal-item',

				className: function () {

					var urlClass = ' bbelementor-template-has-url',
						sourceClass = ' elementor-template-library-template-';

					sourceClass += 'remote';

					return 'elementor-template-library-template' + sourceClass + urlClass;
				},

				ui: function () {
					return {
						previewButton: '.elementor-template-library-template-preview',
					};
				},

				behaviors: {
					insertTemplate: {
						behaviorClass: self.ModalInsertTemplateBehavior
					}
				}
			});

			self.ModalCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-bbelementor-template-modal-templates',

				id: 'bbelementor-template-library-templates',

				childViewContainer: '#bbelementor-modal-templates-container',

				emptyView: function emptyView() {

					return new self.TemplatesEmptyView();
				},

				initialize: function () {

					this.listenTo(BBElementorSectionsEditor.channels.templates, 'filter:change', this._renderChildren);
				},

				filter: function (childModel) {

					var filter = BBElementorSectionsEditor.getFilter('category');
					var searchText = BBElementorSectionsEditor.getFilter('text');

					if (!filter && !searchText) {
						return true;
					}

					if (filter && !searchText) {
						return _.contains(childModel.get('categories'), filter);
					}

					if (searchText && !filter) {
						if (childModel.get('title').toLowerCase().indexOf(searchText) >= 0) {
							return true;
						}
					}

					if (searchText && filter) {
						return _.contains(childModel.get('categories'), filter) && childModel.get('title').toLowerCase().indexOf(searchText) >= 0;
					}

				},

				getChildView: function (childModel) {
					return self.ModalTemplateItemView;
				},

				onRenderCollection: function () {

					var container = this.$childViewContainer,
						items = this.$childViewContainer.children(),
						tab = BBElementorSectionsEditor.getTab();

					if ('bb_elementor_sections_page' === tab || 'local' === tab) {
						return;
					}

					// Wait for thumbnails to be loaded.
					container.imagesLoaded(function () { }).done(function () {
						self.masonry.init({
							container: container,
							items: items
						});
					});
				}

			});

			self.ModalLayoutView = Marionette.LayoutView.extend({

				el: '#bbelementor-template-modal',

				regions: BBElementorSectionsData.modalRegions,

				initialize: function () {

					this.getRegion('modalHeader').show(new self.ModalHeaderView());
					this.listenTo(BBElementorSectionsEditor.channels.tabs, 'filter:change', this.switchTabs);

				},

				switchTabs: function () {
					this.showLoadingView();
					BBElementorSectionsEditor.requestTemplates(BBElementorSectionsEditor.getTab());
				},

				getHeaderView: function () {
					return this.getRegion('modalHeader').currentView;
				},

				getContentView: function () {
					return this.getRegion('modalContent').currentView;
				},

				showLoadingView: function () {
					this.modalContent.show(new self.ModalLoadingView());
				},

				showError: function () {
					this.modalContent.show(new self.ModalErrorView());
				},

				showTemplatesView: function (templatesCollection, categoriesCollection ) {

					if( 0 !== templatesCollection.length ) {
						this.getRegion('modalContent').show(new self.ModalBodyView());
						var contentView = this.getContentView(),
							header = this.getHeaderView();

						BBElementorSectionsEditor.collections.tabs = new self.ModalTabsCollection(BBElementorSectionsEditor.getTabs());

						header.headerTabs.show(new self.ModalTabsCollectionView({
							collection: BBElementorSectionsEditor.collections.tabs
						}));

						contentView.contentTemplates.show(new self.ModalCollectionView({
							collection: templatesCollection
						}));

						contentView.contentFilters.show(new self.FiltersCollectionView({
							collection: categoriesCollection
						}));

						contentView.contentSearch.show(new self.TemplateSearchCollectionView());

					} else {
						this.getRegion('modalContent').show(new self.TemplatesEmptyView());
					}

				}

			});

			self.ModalLoadingView = Marionette.ItemView.extend({
				id: 'bbelementor-template-modal-loading',
				template: '#tmpl-bbelementor-template-modal-loading'
			});

			self.ModalErrorView = Marionette.ItemView.extend({
				id: 'bbelementor-template-modal-error',
				template: '#tmpl-bbelementor-template-modal-error'
			});

		},

		masonry: {

			self: {},
			elements: {},

			init: function (settings) {

				var self = this;
				self.settings = $.extend(self.getDefaultSettings(), settings);
				self.elements = self.getDefaultElements();

				self.run();
			},

			getSettings: function (key) {
				if (key) {
					return this.settings[key];
				} else {
					return this.settings;
				}
			},

			getDefaultSettings: function () {
				return {
					container: null,
					items: null,
					columnsCount: 3,
					verticalSpaceBetween: 30
				};
			},

			getDefaultElements: function () {
				return {
					$container: jQuery(this.getSettings('container')),
					$items: jQuery(this.getSettings('items'))
				};
			},

			run: function () {
				var heights = [],
					distanceFromTop = this.elements.$container.position().top,
					settings = this.getSettings(),
					columnsCount = settings.columnsCount;

				distanceFromTop += parseInt(this.elements.$container.css('margin-top'), 10);

				this.elements.$container.height('');

				this.elements.$items.each(function (index) {
					var row = Math.floor(index / columnsCount),
						indexAtRow = index % columnsCount,
						$item = jQuery(this),
						itemPosition = $item.position(),
						itemHeight = $item[0].getBoundingClientRect().height + settings.verticalSpaceBetween;

					if (row) {
						var pullHeight = itemPosition.top - distanceFromTop - heights[indexAtRow];
						pullHeight -= parseInt($item.css('margin-top'), 10);
						pullHeight *= -1;
						$item.css('margin-top', pullHeight + 'px');
						heights[indexAtRow] += itemHeight;
					} else {
						heights.push(itemHeight);
					}
				});

				this.elements.$container.height(Math.max.apply(Math, heights));
			}
		}

	};

	BBElementorSectionsEditor = {
		modal: false,
		layout: false,
		collections: {},
		tabs: {},
		defaultTab: '',
		channels: {},
		atIndex: null,

		init: function () {

			window.elementor.on(
				'document:loaded',
				window._.bind(BBElementorSectionsEditor.onPreviewLoaded, BBElementorSectionsEditor)
			);

			BBElementorSectionsEditorViews.init();

		},

		onPreviewLoaded: function () {

			this.initBBElementorSectionsTempsButton();

			window.elementor.$previewContents.on(
				'click.addBBElementorSectionsTemplate',
				'.ba-add-section-btn',
				_.bind(this.showTemplatesModal, this)
			);

			this.channels = {
				templates: Backbone.Radio.channel('BBELEMENTOR_EDITOR:templates'),
				tabs: Backbone.Radio.channel('BBELEMENTOR_EDITOR:tabs'),
				layout: Backbone.Radio.channel('BBELEMENTOR_EDITOR:layout'),
			};

			this.tabs = BBElementorSectionsData.tabs;
			this.defaultTab = BBElementorSectionsData.defaultTab;

		},

		initBBElementorSectionsTempsButton: function () {

			setTimeout(function () {
				var $addNewSection = window.elementor.$previewContents.find('.elementor-add-new-section'),
					addBBElementorSectionsTemplate = "<div class='elementor-add-section-area-button ba-add-section-btn' title='Add Elementor Sections Template'><img src='"+BBElementorSectionsData.icon+"'></div>",
					$addBBElementorSectionsTemplate;

				if ($addNewSection.length) {
					$addBBElementorSectionsTemplate = $(addBBElementorSectionsTemplate).prependTo($addNewSection);
				}
			
        window.elementor.$previewContents.on(
            'click.addBBElementorSectionsTemplate',
            '.elementor-editor-section-settings .elementor-editor-element-add',
            function () {

                var $this = $(this),
                    $section = $this.closest('.elementor-top-section'),
                    modelID = $section.data('model-cid');



	            if (-1 !== BBElementorSectionsData.Elementor_Version.indexOf('3.0.')) {
		            if (window.elementor.previewView.collection.length) {
			            $.each(window.elementor.previewView.collection.models, function (index, model) {
				            if (modelID === model.cid) {
					            BBElementorSectionsEditor.atIndex = index;
				            }
			            });
		            }
	            } else {
		            if (elementor.previewView.collection.length) {
                        $.each(elementor.previewView.collection.models, function (index, model) {
                            if (modelID === model.cid) {
                                BBElementorSectionsEditor.atIndex = index;
                            }
                        });
                    }
	            }


								setTimeout(function () {
									var $addNew = $section.prev('.elementor-add-section').find('.elementor-add-new-section');
									$addNew.prepend(addBBElementorSectionsTemplate);
								}, 100);

            }
        );
            }, 100);
		},

		getFilter: function (name) {

			return this.channels.templates.request('filter:' + name);
		},

		setFilter: function (name, value) {
			this.channels.templates.reply('filter:' + name, value);
			this.channels.templates.trigger('filter:change');
		},

		getTab: function () {
			return this.channels.tabs.request('filter:tabs');
		},

		setTab: function (value, silent) {

			this.channels.tabs.reply('filter:tabs', value);

			if (!silent) {
				this.channels.tabs.trigger('filter:change');
			}

		},

		getTabs: function () {

			var tabs = [];

			_.each(this.tabs, function (item, slug) {
				tabs.push({
					slug: slug,
					title: item.title
				});
			});

			return tabs;
		},

		showTemplatesModal: function () {

			this.getModal().show();

			if (!this.layout) {
				this.layout = new BBElementorSectionsEditorViews.ModalLayoutView();
				this.layout.showLoadingView();
			}

			this.setTab(this.defaultTab, true);
			this.requestTemplates(this.defaultTab);

		},

		requestTemplates: function (tabName) {

			if( '' === tabName ) {
				return;
			}

			var self = this,
				tab = self.tabs[tabName];

			self.setFilter('category', false);

			if (tab.data.templates && tab.data.categories) {
				self.layout.showTemplatesView(tab.data.templates, tab.data.categories);
			} else {
				$.ajax({
					url: ajaxurl,
					type: 'get',
					dataType: 'json',
					data: {
						action: 'bb_elementor_sections_get_templates',
						tab: tabName
					},
					success: function (response) {
						console.log("%cTemplates Retrieved Successfully!!", "color: #7a7a7a; background-color: #eee;");

						var templates = new BBElementorSectionsEditorViews.LibraryCollection(response.data.templates),
							categories = new BBElementorSectionsEditorViews.CategoriesCollection(response.data.categories);

						self.tabs[tabName].data = {
							templates: templates,
							categories: categories,
						};

						self.layout.showTemplatesView( templates, categories );

					},
					error: function (err) {
						BBElementorSectionsEditor.closeModal();
					}
				});
			}

		},

		closeModal: function () {
			this.getModal().hide();
		},

		getModal: function () {

			if (!this.modal) {
				this.modal = elementor.dialogsManager.createWidget('lightbox', {
					id: 'bbelementor-template-modal',
					className: 'elementor-templates-modal',
					closeButton: false
				});
			}

			return this.modal;

		}

	};

	$(window).on('elementor:init', BBElementorSectionsEditor.init);

})(jQuery);