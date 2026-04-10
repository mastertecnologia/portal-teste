$(function () {
    "use strict";
    $(function () {
        $(".preloader").fadeOut();
    });
    jQuery(document).on('click', '.mega-dropdown', function (e) {
        e.stopPropagation()
    });
    // ==============================================================
    // This is for the top header part and sidebar part
    // ==============================================================
    var set = function () {
        var width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
        var topOffset = $("body").hasClass("layout-no-topbar") ? 0 : 55;
        var teste = 2; 
        // Alterado de 1170 para 500
        if($("body").hasClass("mini-sidebar")){
            $('.navbar-brand span').hide();
            $(".sidebartoggler i").addClass("ti-menu");
        }
        else {
            $("body").removeClass("mini-sidebar");
            $('.navbar-brand span').show();
        }
        if (width < 1170) {
            $("body").addClass("mini-sidebar");
            $('.navbar-brand span').hide();
            $(".sidebartoggler i").addClass("ti-menu");
        }
        var height = ((window.innerHeight > 0) ? window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            var minH = height + "px";
            var wrappers = document.querySelectorAll(".page-wrapper");
            for (var w = 0; w < wrappers.length; w++) {
                wrappers[w].style.minHeight = minH;
            }
        }
        
    };
    /* Após CSS/fontes carregarem — reduz aviso de layout forçado antes do load completo */
    $(window).on("load", set);
    $(window).on("resize", set);

    // ==============================================================
    // Theme options
    // ==============================================================
    $(".sidebartoggler").on('click', function () {
        if ($("body").hasClass("mini-sidebar")) {
            $("body").trigger("resize");
            $("body").removeClass("mini-sidebar");
            $('.navbar-brand span').show();
        }
        else {
            $("body").trigger("resize");
            $("body").addClass("mini-sidebar");
            $('.navbar-brand span').hide();
        }
    });
    // this is for close icon when navigation open in mobile view
    $(".nav-toggler").click(function () {
        $("body").toggleClass("show-sidebar");
        $(".nav-toggler i").toggleClass("ti-menu");
        $(".nav-toggler i").addClass("ti-close");
    });
    $(".search-box a, .search-box .app-search .srh-btn").on('click', function () {
        $(".app-search").toggle(200);
    });
    // ==============================================================
    // Right sidebar options
    // ==============================================================
    $(".right-side-toggle").click(function () {
        $(".right-sidebar").slideDown(50);
        $(".right-sidebar").toggleClass("shw-rside");
    });
    // ==============================================================
    // This is for the floating labels
    // ==============================================================
    $('.floating-labels .form-control').on('focus blur', function (e) {
        $(this).parents('.form-group').toggleClass('focused', (e.type === 'focus' || this.value.length > 0));
    }).trigger('blur');

    // ==============================================================
    //tooltip
    // ==============================================================
    $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    // ==============================================================
    //Popover
    // ==============================================================
    $(function () {
         $('[data-toggle="popover"]').popover()
    })

    // ==============================================================
    // Perfect scrollbar (sidebar: sem PS no layout-no-topbar — scroll nativo em layout-sidebar-shell.css)
    // Login e outros layouts podem não incluir perfect-scrollbar.jquery.min — só chamar se existir.
    // ==============================================================
    if ($.fn.perfectScrollbar) {
        if ($('body').hasClass('layout-no-topbar')) {
            $('.scroll-sidebar').each(function () {
                try {
                    $(this).perfectScrollbar('destroy');
                } catch (err) { /* não estava inicializado */ }
            });
            $('.right-side-panel, .message-center, .right-sidebar').perfectScrollbar();
        } else {
            $('.scroll-sidebar, .right-side-panel, .message-center, .right-sidebar').perfectScrollbar();
        }
    }
    // ==============================================================
    // Resize all elements
    // ==============================================================
    $("body").trigger("resize");
    // ==============================================================
    // To do list
    // ==============================================================
    $(".list-task li label").click(function () {
        $(this).toggleClass("task-done");
    });
    // ==============================================================
    // Collapsable cards
    // ==============================================================
    $('a[data-action="collapse"]').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.card').find('[data-action="collapse"] i').toggleClass('ti-minus ti-plus');
        $(this).closest('.card').children('.card-body').collapse('toggle');
    });
    // Toggle fullscreen
    $('a[data-action="expand"]').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.card').find('[data-action="expand"] i').toggleClass('mdi-arrow-expand mdi-arrow-compress');
        $(this).closest('.card').toggleClass('card-fullscreen');
    });
    // Close Card
    $('a[data-action="close"]').on('click', function () {
        $(this).closest('.card').removeClass().slideUp('fast');
    });
    // ==============================================================
    // ecommerce sidebar (#eco-spark) — layouts sem jquery.sparkline.js não definem $.fn.sparkline
    // ==============================================================
    var sparklineLogin = function () {
            if (typeof $.fn.sparkline !== 'function' || !$('#eco-spark').length) {
                return;
            }
            $('#eco-spark').sparkline([6, 10, 9, 11, 9, 10, 12, 11, 10, 7, 11, 9, 8, 10, 9, 12], {
                type: 'bar',
                height: '50',
                barWidth: '4',
                resize: true,
                barSpacing: '7',
                barColor: '#33CCFF'
            });
        },
        sparkResize;
    $(window).on("resize", function (e) {
        clearTimeout(sparkResize);
        sparkResize = setTimeout(sparklineLogin, 500);
    });
    sparklineLogin();

    // ==============================================================
    // Color variation (apenas skins claras; escuras bloqueadas)
    // ==============================================================

    var allSkinClasses = [
        "skin-default",
        "skin-green",
        "skin-red",
        "skin-blue",
        "skin-purple",
        "skin-megna",
        "skin-default-dark",
        "skin-green-dark",
        "skin-red-dark",
        "skin-blue-dark",
        "skin-purple-dark",
        "skin-megna-dark"
    ];
    var lightSkins = [
        "skin-default",
        "skin-green",
        "skin-red",
        "skin-blue",
        "skin-purple",
        "skin-megna"
    ];
    var defaultLightSkin = "skin-green";

    function get(name) {
        if (typeof (Storage) !== 'undefined') {
            return localStorage.getItem(name)
        }
        else {
            window.alert('Please use a modern browser to properly view this template!')
        }
    }
    function store(name, val) {
        if (typeof (Storage) !== 'undefined') {
            localStorage.setItem(name, val)
        }
        else {
            window.alert('Please use a modern browser to properly view this template!')
        }
    }

    function stripAllSkins($body) {
        $.each(allSkinClasses, function (i) {
            $body.removeClass(allSkinClasses[i]);
        });
    }

    /**
     * Troca de cor da skin (Material): só aceita classes sem sufixo -dark; inválidas caem em skin-green.
     */
    function changeSkin(cls) {
        if ($.inArray(cls, lightSkins) === -1) {
            cls = defaultLightSkin;
        }
        var $body = $('body');
        stripAllSkins($body);
        $body.addClass(cls);
        store('skin', cls);
        return false;
    }

    /** Remove skin escura do body (HTML antigo, cache, extensão) e normaliza localStorage. */
    function sanitizeDarkSkinsOnLoad() {
        var $body = $('body');
        var i;
        for (i = 0; i < allSkinClasses.length; i++) {
            if (allSkinClasses[i].indexOf('-dark') !== -1 && $body.hasClass(allSkinClasses[i])) {
                stripAllSkins($body);
                $body.addClass(defaultLightSkin);
                store('skin', defaultLightSkin);
                return;
            }
        }
        var stored = get('skin');
        if (stored && $.inArray(stored, lightSkins) === -1) {
            store('skin', defaultLightSkin);
        }
    }

    function setup() {
        sanitizeDarkSkinsOnLoad();

        $('[data-skin]').each(function () {
            var s = $(this).data('skin');
            if ($.inArray(s, lightSkins) === -1) {
                $(this).closest('li').hide();
            }
        });

        $('[data-skin]').on('click', function (e) {
            if ($(this).hasClass('knob')) return;
            e.preventDefault();
            var next = $(this).data('skin');
            if ($.inArray(next, lightSkins) === -1) {
                changeSkin(defaultLightSkin);
                return false;
            }
            changeSkin(next);
        });
    }

    setup()
    $("#themecolors").on("click", "a", function () {
        $("#themecolors li a").removeClass("working"),
        $(this).addClass("working")
    })
});
