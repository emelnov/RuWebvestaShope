(function (Drupal, cookies) {

  "use strict";

  Drupal.behaviors.iplessWatching = {
    attach: function (context, settings) {
      var base = this;

      const elements = once('ipless_watching', 'body', context);

      elements.forEach(function (e) {
        base.init(settings.ipless)
            .buildUi(context)
            .setWatch();
      });
    },
    init: function (options) {

      if (cookies.get("ipless.disabled") == 1) {
        this.status = 0;
      }
      else {
        this.status = this.options.default_status;
      }

      this.options = Object.assign({}, this.options, options)
      return this;
    },
    watch: function () {
      if (this.getStatus() == 0) {
        return;
      }
      var base = this;
      Drupal.ajax({
            url: "/ipless/watching",
            submit: {
              libraries: this.getLibrariesKeys(),
              time: this.getLastModifiedTime()
            },
            success: function (data) {

              Object.keys(data).forEach(function (key, i) {
                var library = data[key];
                base.updateLastModifiedTime(library.library);

                var out = library.output.replace("public://", "");
                var href = document.querySelector('link[href*="' + out + '"').getAttribute("href");
                var url = new URL(href, window.location.href);
                document.querySelector('link[href="' + href + '"').setAttribute("href", url.pathname + "?rev=" + Date.now());
              });

              base.setError(false).setWatch();
            },
            error: function (response) {
              var error = response.responseText;
              if (response.responseJSON !== undefined && response.responseJSON.error !== undefined) {
                error = response.responseJSON.error;
              }
              base.setError(error).setWatch();
            }
          }
      ).execute();
    },
    setWatch: function () {
      var me = this;
      this.timer = setTimeout(function(){
          me.watch();
      }, this.options.refresh_rate);
      return this;
    },
    clearWatch: function () {
      clearTimeout(this.timer);
    },
    getLastModifiedTime: function () {
      var last_m_time = 0;
      var libs = this.getLibraries();
      Object.keys(libs).forEach(function (key, i) {
        var library = libs[key];
        if (library.last_m_time > last_m_time) {
          last_m_time = library.last_m_time;
        }
      });
      return last_m_time;
    },
    updateLastModifiedTime: function (library) {
      var base = this;
      var time = Math.floor(Date.now() / 1000);
      var libs = this.getLibraries();
      Object.keys(libs).forEach(function (key, i) {
        if (libs[key].library === library) {
          base.options.libraries[key].last_m_time = time;
        }
      });
      return this;
    },
    getLibraries: function () {
      return this.options.libraries;
    },
    getLibrariesKeys: function () {
      var libraries = [];
      var libs = this.getLibraries();
      Object.keys(this.getLibraries()).forEach(function (key, i) {
        libraries.push(libs[key].library);
      });
      return libraries;
    },
    toggleStatus: function () {
      switch (this.getStatus()) {
        case 0:
          this.status = 1;
          cookies.set("ipless.disabled", 0);
          this.setWatch();
          break;
        case 1:
          this.status = 0;
          cookies.set("ipless.disabled", 1);
          this.clearWatch();
          break;
      }
      return this;
    },
    getStatus: function () {
      return this.status;
    },
    setError: function (message) {
      if (message === false) {
        this.error = 0;
        this.error_message = "";
      }
      else {
        this.error = 1;
        this.error_message = message;
      }
      this.uiRefreshStatus();
      return this;
    },
    buildUi: function (context) {
      var base = this;
      base.ui = document.createElement('div');
      base.ui.innerHTML = Drupal.theme("ipless_ui");
      context.querySelector("body").append(base.ui);

      base.uiRefreshStatus();

      base.ui.querySelector(".handle").addEventListener("click", function () {
        base.toggleStatus().uiRefreshStatus();
      });

      // Webprofiler loaded.
      if (document.querySelectorAll(".sf-toolbar").length > 0) {
        base.ui.classList.add("profiler-loaded");
      }

      return this;
    },
    uiRefreshStatus: function () {
      var status = this.ui.querySelector(".status");
      switch (this.getStatus()) {
        case 0:
          status.classList.remove("play")
          status.classList.add("pause");
          break;
        case 1:
          status.classList.remove("pause")
          status.classList.add("play");
          break;
      }

      if (this.error) {
        status.classList.add("error")
        status.setAttribute("title", this.error_message);
      }
      else {
        status.classList.remove("error")
        status.setAttribute("title", "");
      }
      return this;
    },
    status: 0,
    error: 0,
    error_message: "",
    timer: null,
    options: {
      libraries: [],
      refresh_rate: 2000,
      default_status: 1
    }
  };

  Drupal.theme.ipless_ui = function () {
    return '<div class="ipless-ui"><div class="handle">{Less} <span class="status"></span></div></div>';
  };

})(Drupal, window.Cookies);
