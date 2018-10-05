define([
    'jquery',
    'jquery/ui',
    "mage/translate"
], function ($) {
    'use strict';
    $.widget('mage.reorderProduct', {
        options: {
            reorderContent: '',
            productCheckbox: '',
            productQty: '',
            selectAllCheckbox: '',
            numberSelected:'',
            addToCart:'',
            deselectAll:'',
            searchBox:'',
            searchReorder:'',
            clearReorder:'',
            loader:'',
            fav:[],
            favs:[]
        },

        _create: function () {
            var $widget = this;
            $widget._SelectChecked();
            $widget._EventListener();
        },

        _EventListener: function () {
            var $widget = this;
            var reorderContent = this.options.reorderContent, searchBox = this.options.searchBox;
            $(reorderContent).on('click', this.options.selectAllCheckbox, function () {
                return $widget._OnClick($(this));
            });

            $(reorderContent).on('click', this.options.productCheckbox, function () {
                return $widget._OnClickSingle($(this));
            });

            $(reorderContent).on('input', this.options.productQty, function () {
                return $widget._InputChange($(this));
            });

            $(reorderContent).off('click', ".pages a");

            $(reorderContent).on('click', ".pages a", function(event) {
                event.preventDefault();
                var reg = new RegExp('[?&]p=([^&#]*)', 'i'), page = null, name = $(searchBox).val();;
                page = reg.exec($(this).attr('href'))[1];
                return $widget._UpdateTable(page, name);
            });

            $(this.options.deselectAll).on('click', function () {
                return $widget._DeselectAll();
            });

            $(this.options.searchReorder).on('click', function () {
                return $widget._SearchProduct();
            });

            $(this.options.clearReorder).on('click', function () {
                return $widget._ClearSearch();
            });

            $(this.options.addToCart).on('click', function (e) {
                e.preventDefault();
                return $widget._AddToCart($(this));
            });
        },

        // pagination
        _UpdateTable: function (pageno, name) {
            var $widget = this, loader = this.options.loader;
            $.ajax({
                type:"get",
                url:'/quick-reorder/index/search?p='+pageno+'&product_name='+name,
                beforeSend: function() {
                    $(loader).show();
                },
                success: function(data) {
                    jQuery("#reoderproduct-content").html(data);
                    $widget._SelectChecked();
                     $(loader).hide();
                }
            });
        },

        // autoselect checkbox on product load
        _SelectChecked: function () {
            var item_reorder = JSON.parse(sessionStorage.getItem('item_reorder'));
            var fav,favs = this.options.favs = [];
            var productCheckbox = this.options.productCheckbox;
            var selectAllCheckbox = this.options.selectAllCheckbox;
            if (item_reorder) {
                for (var i = 0; i < item_reorder.length; i++) {
                    $('#item_' + item_reorder[i].id).prop('checked', item_reorder[i].checked);
                    $('#qty_' + item_reorder[i].id).val(item_reorder[i].qty);
                    fav = {
                        id: item_reorder[i].id,
                        parentId: item_reorder[i].parentId,
                        checked: item_reorder[i].checked,
                        qty: item_reorder[i].qty
                    };
                    favs.push(fav);
                    this.options.favs = favs;
                }
                if ($(productCheckbox).length && $(productCheckbox + ':checked').length == $(productCheckbox).length) {
                    $(selectAllCheckbox).attr('checked', true);
                }
            } else {
                $(selectAllCheckbox).prop('checked', false);
                $(productCheckbox).prop('checked', false);
            }
            $(this.options.numberSelected).text(favs.length);
        },

        // click on select all checkbox
        _OnClick: function ($this) {
            var productCheckbox = this.options.productCheckbox;
            if ($this.is(':checked')) {
                if ($(productCheckbox).length > 0) {
                    $(productCheckbox).each(function () {
                        if (!$(this).is(':checked')) {
                            $(this).trigger('click');
                        }
                    })
                }
            } else {
                $(productCheckbox).each(function () {
                    if ($(this).is(':checked')) {
                        $(this).trigger('click');
                    }
                });
            }
        },

        // click on product's checkbox
        _OnClickSingle: function ($this) {
            var $widget = this;
            var favs = this.options.favs;
            var fav = [];
            if ($this.is(':checked')) {
                var item_qty = $('#qty_' + $this.val()).val();
                if (item_qty === undefined) {
                    item_qty = 1;
                }
                fav = {
                    id: $this.val(),
                    parentId: $('#parent_' + $this.val()).val(),
                    checked: $this.prop('checked'),
                    qty: item_qty
                };
                favs.push(fav);
                $('#qty_' + $this.val()).addClass('validate-no-empty validate-number validate-greater-than-zero');
            } else {
                $widget._Remove($this.val(), favs);
                $(this.options.selectAllCheckbox).prop('checked', false);
                $('#qty_' + $this.val()).removeClass('validate-no-empty validate-number validate-greater-than-zero');
            }
            $widget._setSessionStorage(favs);
        },

        // set the products into session storage
        _setSessionStorage: function (favs) {
            this.options.favs = favs;
            sessionStorage.setItem("item_reorder", JSON.stringify(favs));
            $(this.options.numberSelected).text(favs.length);
        },

        // on change of product's quantity
        _InputChange: function ($this) {
            var $widget = this;
            var favs = this.options.favs;
            var fav = [];

            var item_qty = $this.val();
            var item_id = $this.attr('id').split('_');

            if (favs.length > 0 && $widget._Search(item_id[1], favs)) {
                $widget._Remove(item_id[1], favs);

                fav = {
                    id: item_id[1],
                    parentId: $('#parent_' + item_id[1]).val(),
                    checked: $('#item_' + item_id[1]).prop('checked'),
                    qty: item_qty
                };
                favs.push(fav);
                $widget._setSessionStorage(favs);
            }
        },

        _Remove: function (key, list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].id === key) {
                    list.splice(i, 1);
                }
            }
        },

        _Search: function (key, list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].id === key) {
                    return list[i];
                }
            }
        },

        // click on deselect all checakbox
        _DeselectAll: function () {
            var $widget = this;
            sessionStorage.clear();
            $widget._SelectChecked();
        },

        // search products
        _SearchProduct: function () {
            var $widget = this, loader = this.options.loader;
            var value = $(this.options.searchBox).val();
            $.ajax({
                type: "post",
                url: "/quick-reorder/index/search",
                data: { product_name: value },
                beforeSend: function() {
                     $(loader).show();
                },
                success: function(data) {
                    jQuery("#reoderproduct-content").html(data);
                    $widget._SelectChecked();
                    $(loader).hide();
                }
            })
        },

        // clear search
        _ClearSearch: function ($this) {
            var $widget = this;
            $(this.options.searchBox).val('');
            $widget._SearchProduct();
        },

        // Add products into cart
        _AddToCart: function ($this) {
            var $widget = this, loader = this.options.loader;
            $(loader).show();
            var url = $this.data('url');
            var data = [];
            if ($('#list-reoderproduct').valid()) {
                var item_reorder = JSON.parse(sessionStorage.getItem('item_reorder'));
                if (!item_reorder || item_reorder.length == 0) {
                    $(loader).hide();
                    alert($.mage.__('Please select items !'));
                    return false;
                } else {
                    data = {
                        item: sessionStorage.getItem('item_reorder')
                    };
                }
                $widget._SendAjax($this, url, data)
            } else {
                 $(loader).hide();
            }
        },

        _SendAjax: function ($this, url, data) {
            var $widget = this, loader = this.options.loader;
            $.ajax({
                type: 'post',
                url: url,
                data: data,
                dataType: 'json',
                success: function (data) {
                    $(loader).hide();
                    $('html,body').animate({
                        scrollTop: $('body').offset().top
                    },'slow');
                    if (data.status == 'SUCCESS') {
                        sessionStorage.clear();
                        $widget._SelectChecked();
                    }
                }
            });
        }
    });
    return $.mage.reorderProduct;
});