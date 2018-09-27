define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'lighter',
    'magnificPopup',
    'datatables',
    'reorder',
    "mage/translate",
    'mage/mage',
    "domReady!"
], function ($,customerData) {
    'use strict';
    $.widget('mage.reorderProduct', {
        options: {
            listItem:[],
            lengthMenuKey:[],
            lengthMenuValue:[],
            pageLength:[],
            ordersort:'',
            showcolumns:'',
            urlUpdateCart:'',
            urlRedirectToCart:'',
            urlRedirectToWishlist:'',
            fav:[],
            favs:[]
        },

        _init: function () {
            if (this.options.listItem !== '') {
                this._RenderTable();
            } else {
                console.log('reorderProduct: No input data received');
            }
        },

        _create: function () {
            var $widget = this;
            // remove redirect-url
            if ($(window).width() < 768) {
                $('.list-reoderproduct-d').width($(window).width() - 30);
            }

            $(window).resize(function () {
                if ($(window).width() < 768) {
                    $('.list-reoderproduct-d').width($(window).width() - 30);
                }
            })
            $widget._SelectChecked();
            $widget._EventListener();
        },

        _RenderTable: function () {
             // DataTable
            var $widget = this;
            // Create table
            var table = $('#reorder_product').DataTable({
                "language": {
                    "lengthMenu": $.mage.__('Show _MENU_ per page'),
                    "info": $.mage.__('Items _START_ - _END_ of _TOTAL_ '),
                    "infoFiltered": $.mage.__('- filtered from _MAX_ items'),
                    "infoEmpty": $.mage.__('No Items'),
                    "emptyTable": $.mage.__('There are no items to show in reorder list')
                },
                "lengthMenu": [
                    $widget.options.lengthMenuKey, $widget.options.lengthMenuValue
                ],
                "pageLength": $widget.options.pageLength,
                "columnDefs": [
                    { "bSortable": false, "aTargets": [ 0, 1, 4, 8 ] }
                ],
                "dom": '<"top"if>rt<"bottom"lp><"clear">',
                "order": [[ $widget.options.ordersort, "asc" ]],
                stateSave: true,
                responsive: true
            });
            $('#reorder_product').on('draw.dt', function () {
                var checkedList = {};
                $.each($widget.options.favs, function(k, fav) {
                    if (fav.checked) {
                        checkedList[fav.id] = true;
                    }
                });
                $(this).find('input.reorder-select-item').each(function () {
                    if (checkedList[$(this).val()]) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                });
            });
            //Show hide column in table
            $('#show-hide-colum-reorder').click(function (e) {
                e.stopPropagation();
                $('div#control_sh').slideToggle("fast");
            });

            var state = table.state.loaded();
            if (state != null) {
                if (state.length > 0 && state['search']['search'] != '') {
                    $('#searchbox-reorder').val(state['search']['search']);
                }
            }

            $(window).on('click',function () {
                 $('div#control_sh').hide();
            })

            // Search data from table
            $("#searchbox-reorder").on("keyup search input paste cut", function () {
               table.search(this.value).draw();
            });
            var i;
            for (i = 0; i <= 8; i++) {
                 var columndf = table.column(i);
                 var showcolumns = $widget.options.showcolumns;
                 if (showcolumns.indexOf(i) != -1) {
                        $('#control_sh input').eq(i).prop('checked',true);
                        columndf.visible(true);
                 } else {
                        columndf.visible(false);
                        $('#control_sh input').eq(i).prop('checked',false);
                 }
            }

            $("#show-hide-colum-reorder,#control_sh").appendTo("#list-reoderproduct .top");

            $('#control_sh input').on('click', function (e) {
                e.stopPropagation();
                var column = table.column($(this).attr('data-column'));
                column.visible(!column.visible());
            });

            $('#list-reoderproduct').css('display','block');
        },
        
        _EventListener: function () {

            var $widget = this;

            $(document).on('click', '#reorder-select-all', function () {
                return $widget._OnClick($(this));
            });

            $(document).on('click', '.reorder-select-item', function () {
                return $widget._OnClickSingle($(this));
            });

            $(document).on('click', '.reorder-quickview', function () {
                return $widget._QuickView($(this));
            });

            $(document).on('input', '.qty-reoderproduct', function () {
                return $widget._InputChange($(this));
            });

            $('body').on('click','#add-all-to-cart-reorder,#add-all-to-wishlist-reorder,.bt-reoderproduct',function (e) {
                e.preventDefault();
                return $widget._AddToCart($(this));
            })
        
        },

        _OnClick: function ($this) {
            var list_item = this.options.listItem;
            var fav, favs = [];
            if ($this.is(':checked')) {
                if ($('.reorder-select-item').length > 0) {
                    $('.reorder-select-item').each(function () {
                        if (!$(this).is(':checked')) {
                            $(this).trigger('click');
                        }
                    })
                    favs.length = 0;
                    for (var i = 0; i < list_item.length; i++) {
                        var item_qty = $('#qty_' + list_item[i]).val();
                        if (item_qty === undefined) {
                            item_qty = 1
                        }
                        fav = {
                            id: list_item[i],
                            checked: true,
                            qty: item_qty
                        };
                        favs.push(fav);
                    }
                    var all = {
                        id: 'reorder-select-all',
                        checked: true,
                        qty: false
                    };
                    favs.push(all);
                    sessionStorage.setItem("item_reorder", JSON.stringify(favs));
                    $('.number-selected').text(list_item.length);
                    this.options.favs = favs;
                }
            } else {
                $('.reorder-select-item').each(function () {
                    if ($(this).is(':checked')) {
                        $(this).trigger('click');
                    }
                })
                $('.number-selected').text(0);
                favs.length = 0;
                this.options.favs = [];
                sessionStorage.clear();
            }
        },

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
                    checked: $this.prop('checked'),
                    qty: item_qty
                };

                favs.push(fav);
                $('.number-selected').text(parseInt($('.number-selected').text()) + 1);
                $('#qty_' + $this.val()).addClass('validate-no-empty validate-number validate-greater-than-zero');
            } else {
                $widget._Remove($this.val(), favs);
                $('#reorder-select-all').prop('checked', false);
                $('#qty_' + $this.val()).removeClass('validate-no-empty validate-number validate-greater-than-zero');
                $('.number-selected').text(parseInt($('.number-selected').text()) - 1);
            }

            sessionStorage.setItem("item_reorder", JSON.stringify(favs));
            this.options.favs = favs;
        },
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
                    checked: $('#item_' + item_id[1]).prop('checked'),
                    qty: item_qty
                };

                favs.push(fav);
                this.options.favs = favs;
                sessionStorage.setItem("item_reorder", JSON.stringify(favs));
            }
        },

        _SelectChecked: function () {
            var $widget = this;
            var item_reorder = JSON.parse(sessionStorage.getItem('item_reorder'));
            var fav,favs = [];
            var j = 0;
            if (item_reorder) {
                for (var i = 0; i < item_reorder.length; i++) {
                    $('#item_' + item_reorder[i].id).prop('checked', item_reorder[i].checked);
                    $('#qty_' + item_reorder[i].id).val(item_reorder[i].qty);
                    fav = {
                        id: item_reorder[i].id,
                        checked: item_reorder[i].checked,
                        qty: item_reorder[i].qty
                    };
                    if (item_reorder[i].checked) {
                        j++;
                    }
                    favs.push(fav);
                }
                if ($widget._Search("reorder-select-all", item_reorder)) {
                    $('#reorder-select-all').prop('checked', true);
                    $('.number-selected').text(j - 1);
                } else {
                    $('.number-selected').text(j);
                }
            } else {
                $('#reorder-select-all').prop('checked', false);
                $('input.reorder-select-item').prop('checked', false);
            }
        },
        // Popup
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

        _AddToCart: function ($this) {
            var $widget = this;
            $this.addClass('loading-preorder-product');
            if ($this.data('item-id')) {
                $("#qty_" + $this.data('item-id')).addClass("validate-no-empty validate-number validate-greater-than-zero");
            }
            
            var url = $this.data('url');
            var data = [];
            if ($('#list-reoderproduct').valid()) {
                if ($this.data('item-id')) {
                    $("#qty_" + $this.data('item-id')).removeClass("validate-no-empty validate-number validate-greater-than-zero");
                }
                if ($this.attr('id') == 'add-all-to-cart-reorder' || $this.attr('id') == 'add-all-to-wishlist-reorder') {
                    var item_reorder = JSON.parse(sessionStorage.getItem('item_reorder'));
                    if (!item_reorder || item_reorder.length == 0) {
                        $this.removeClass('loading-preorder-product');
                        alert($.mage.__('Please select items !'));
                        return false;
                    } else {
                        data = {
                            item: sessionStorage.getItem('item_reorder'),
                            type: 'addmultiple'
                        };
                    }
                } else {
                    url = $this.data('url') + 'qty/' + $("#qty_" + $this.data('item-id')).val();
                }
                $widget._SendAjax($this, url, data)
            } else {
                $this.removeClass('loading-preorder-product');
            }
        },

        _SendAjax: function ($this, url, data) {
            var $widget = this;
            var redirecttowishlist = this.options.urlRedirectToWishlist;
            var redirecttocart = this.options.urlRedirectToCart;
            $.ajax({
                type: 'post',
                url: url,
                data: data,
                dataType: 'json',
                success: function (data) {
                    $this.removeClass('loading-preorder-product');
                    $('html,body').animate({
                        scrollTop: $('body').offset().top
                    },'slow');
                    setTimeout(function () {
                        if (data.status == 'SUCCESS') {
                            sessionStorage.clear();
                            if ($this.attr('id') == 'add-all-to-cart-reorder' || $this.attr('id') == 'add-all-to-wishlist-reorder') {
                                $widget._SelectChecked();
                            }
                            if (redirecttocart != '' && data.type == 'cart') {
                                window.location.href = redirecttocart;
                            } else if (redirecttowishlist != '' && data.type == 'wishlist') {
                                window.location.href = redirecttowishlist;
                            } else {
                                window.location.href = '';
                            }
                        } else {
                            // window.location.href = '';
                        }
                    }, 1000);
                },
                error: function () {
                    // window.location.href = '';
                }
            });
        },

        _QuickView: function ($this) {
            var prodUrl = $this.attr('data-quickview-url');
            if (prodUrl.length) {
                var url = this.options.urlUpdateCart;
                $.magnificPopup.open({
                    items: {
                      src: prodUrl
                    },
                    type: 'iframe',
                    closeOnBgClick: false,
                    preloader: true,
                    tLoading: '',
                    callbacks: {
                        open: function () {
                          $('.mfp-preloader').css('display', 'block');
                        },
                        beforeClose: function () {
                            $('[data-block="minicart"]').trigger('contentLoading');
                            var sections = ['cart'];
                            customerData.invalidate(sections);
                            customerData.reload(sections, true);
                        },
                        close: function () {
                          $('.mfp-preloader').css('display', 'none');
                        },
                        afterClose: function () {
                        }
                      }
                });
            }
        }
    });
    return $.mage.reorderProduct;
});
