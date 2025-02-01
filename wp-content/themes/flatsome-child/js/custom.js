jQuery(document).ready(function ($) {
  function isTouchDevice() {
    return window.matchMedia("(max-width: 1024px)").matches;
  }

  // Function to set submenu width to `.main-menu` width
  function updateSubMenuWidth() {
    let mainMenuWidth = $(
      ".flex-col.hide-for-medium.flex-right.flex-grow.main-menu"
    ).outerWidth();
    $(".secondary-menu.second-level").css("width", mainMenuWidth + "px");
  }

  updateSubMenuWidth(); // Set width on load
  $(window).on("resize", updateSubMenuWidth); // Recalculate when the window changes

  // For pc (hover)
  $(".secondary-menu.first-level > li").hover(
    function () {
      if (!isTouchDevice()) {
        let $submenu = $(this).children(".secondary-menu.second-level");
        $submenu.addClass("force-flex");
        $submenu
          .css({ opacity: 0, transform: "translateX(-10px)" })
          .animate({ opacity: 1, transform: "translateX(0)" }, 300);
      }
    },
    function () {
      if (!isTouchDevice()) {
        let $submenu = $(this).children(".secondary-menu.second-level");
        $submenu.animate(
          { opacity: 0, transform: "translateX(-10px)" },
          300,
          function () {
            $(this).removeClass("force-flex ");
          }
        );
      }
    }
  );

  // For tablets (click)
  $(".secondary-menu.first-level > li > a").on("click", function (e) {
    if (isTouchDevice()) {
      e.preventDefault();
      let $submenu = $(this).siblings(".secondary-menu.second-level");

      if ($submenu.length) {
        if (!$submenu.is(":visible")) {
          // Закрываем все остальные подменю перед открытием нового
          $(".secondary-menu.second-level")
            .not($submenu)
            .each(function () {
              $(this).animate(
                { opacity: 0, transform: "translateX(-10px)" },
                300,
                function () {
                  $(this).css("display", "none").removeClass("force-flex"); // Удаляем force-flex при закрытии
                }
              );
            });

          $(".secondary-menu.first-level > li > a")
            .not(this)
            .removeClass("active");

          // Открываем новое меню с анимацией и добавляем force-flex
          $submenu
            .addClass("force-flex")
            .css({ opacity: 0, transform: "translateX(-10px)" })
            .animate({ opacity: 1, transform: "translateX(0)" }, 300);

          $(this).addClass("active");
        } else {
          // Анимация закрытия и удаление force-flex
          $submenu.animate(
            { opacity: 0, transform: "translateX(-10px)" },
            300,
            function () {
              $(this).css("display", "none").removeClass("force-flex");
            }
          );

          $(this).removeClass("active");
        }
      }
    }
  });

  // Close on click outside
  $(document).on("click", function (e) {
    if (
      isTouchDevice() &&
      !$(e.target).closest(".secondary-menu.first-level").length
    ) {
      $(".secondary-menu.second-level").each(function () {
        $(this).animate(
          { opacity: 0, transform: "translateX(-10px)" },
          300,
          function () {
            $(this).css("display", "none").removeClass("force-flex"); // Удаляем force-flex при закрытии
          }
        );
      });

      $(".secondary-menu.first-level > li > a").removeClass("active");
    }
  });
});
