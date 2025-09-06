/**
 * AI Events Pro - Admin JavaScript
 */

(function ($) {
  "use strict";

  class AIEventsAdmin {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.initCharts();
      this.initBulkActions();
    }

    bindEvents() {
      // Tab switching
      $(document).on("click", ".nav-tab", this.handleTabSwitch);

      // API testing
      $(document).on("click", ".test-api-btn", this.testApiConnection);

      // Event syncing
      $(document).on("click", "#sync-events-btn", this.syncEvents);

      // Cache clearing
      $(document).on("click", "#clear-cache-btn", this.clearCache);

      // Bulk import
      $(document).on("click", "#bulk-import-btn", this.bulkImportEvents);

      // Form validation
      $(document).on("submit", "form", this.validateForm);

      // Real-time validation
      $(document).on("input", 'input[type="url"]', this.validateUrl);
      $(document).on("input", 'input[type="email"]', this.validateEmail);

      // Auto-save drafts (for event creation)
      if ($("body").hasClass("post-type-ai_event")) {
        this.initAutoSave();
      }
    }

    handleTabSwitch(e) {
      e.preventDefault();

      const $tab = $(this);
      const target = $tab.attr("href");

      $(".nav-tab").removeClass("nav-tab-active");
      $tab.addClass("nav-tab-active");

      $(".tab-content").removeClass("active");
      $(target).addClass("active");

      // Save active tab to localStorage
      localStorage.setItem("ai-events-active-tab", target);
    }

    async testApiConnection(e) {
      const $button = $(this);
      const apiType = $button.data("api");
      const $input = $button.siblings("input");
      const apiKey = $input.val().trim();

      if (!apiKey) {
        alert(
          ai_events_admin.strings.api_no_key || "Please enter an API key first."
        );
        return;
      }

      $button
        .prop("disabled", true)
        .text(ai_events_admin.strings.testing_api || "Testing...")
        .addClass("testing");

      try {
        const response = await $.post(ajaxurl, {
          action: "test_api_connection",
          nonce: ai_events_admin.nonce,
          api_type: apiType,
          api_key: apiKey,
        });

        if (response.success) {
          $button.addClass("success");
          alert(
            ai_events_admin.strings.api_success || "Connection successful!"
          );
        } else {
          $button.addClass("error");
          alert("Error: " + response.data);
        }
      } catch (error) {
        $button.addClass("error");
        alert("Connection failed: " + error.statusText);
      } finally {
        $button
          .prop("disabled", false)
          .text(ai_events_admin.strings.test_connection || "Test Connection")
          .removeClass("testing");

        setTimeout(() => {
          $button.removeClass("success error");
        }, 3000);
      }
    }

    async syncEvents(e) {
      const $button = $(this);
      const $results = $("#sync-results");

      const location = $("#sync_location").val().trim();
      const radius = $("#sync_radius").val();
      const limit = $("#sync_limit").val();

      if (!location) {
        alert("Please enter a location.");
        return;
      }

      $button.prop("disabled", true).text("Syncing...");
      $results.removeClass("success error").empty();

      try {
        const response = await $.post(ajaxurl, {
          action: "sync_events",
          nonce: ai_events_admin.nonce,
          location: location,
          radius: radius,
          limit: limit,
        });

        if (response.success) {
          $results.addClass("success").html(`
                        <strong>Success!</strong> ${response.data.message}
                        <br><small>Events synced: ${
                          response.data.events_count || 0
                        }</small>
                    `);
        } else {
          $results.addClass("error").html(`
                        <strong>Error:</strong> ${response.data}
                    `);
        }
      } catch (error) {
        $results.addClass("error").html(`
                    <strong>Error:</strong> Failed to sync events. Please try again.
                `);
      } finally {
        $button.prop("disabled", false).text("Sync Events Now");
      }
    }

    async clearCache(e) {
      if (!confirm("Are you sure you want to clear the events cache?")) {
        return;
      }

      const $button = $(this);
      const $results = $("#sync-results");

      $button.prop("disabled", true).text("Clearing...");

      try {
        const response = await $.post(ajaxurl, {
          action: "clear_events_cache",
          nonce: ai_events_admin.nonce,
        });

        if (response.success) {
          $results.removeClass("error").addClass("success").html(`
                        <strong>Success!</strong> ${response.data}
                    `);
        } else {
          $results.removeClass("success").addClass("error").html(`
                        <strong>Error:</strong> ${response.data}
                    `);
        }
      } catch (error) {
        $results.removeClass("success").addClass("error").html(`
                    <strong>Error:</strong> Failed to clear cache.
                `);
      } finally {
        $button.prop("disabled", false).text("Clear Cache");
      }
    }

    bulkImportEvents(e) {
      const $button = $(this);
      const $fileInput = $("#csv_file");
      const file = $fileInput[0].files[0];

      if (!file) {
        alert("Please select a CSV file.");
        return;
      }

      if (!file.name.toLowerCase().endsWith(".csv")) {
        alert("Please select a valid CSV file.");
        return;
      }

      const formData = new FormData();
      formData.append("action", "bulk_import_events");
      formData.append("nonce", ai_events_admin.nonce);
      formData.append("csv_file", file);

      $button.prop("disabled", true).text("Importing...");

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            alert("Import successful: " + response.data);
            location.reload();
          } else {
            alert("Import failed: " + response.data);
          }
        },
        error: function () {
          alert("Import failed. Please try again.");
        },
        complete: function () {
          $button.prop("disabled", false).text("Import Events");
        },
      });
    }

    validateForm(e) {
      const $form = $(this);
      let isValid = true;

      // Validate required fields
      $form.find("[required]").each(function () {
        const $field = $(this);
        if (!$field.val().trim()) {
          $field.addClass("error");
          isValid = false;
        } else {
          $field.removeClass("error");
        }
      });

      // Validate URLs
      $form.find('input[type="url"]').each(function () {
        const $field = $(this);
        const url = $field.val().trim();

        if (url && !isValidUrl(url)) {
          $field.addClass("error");
          isValid = false;
        } else {
          $field.removeClass("error");
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert("Please correct the highlighted fields.");
      }
    }

    validateUrl(e) {
      const $field = $(this);
      const url = $field.val().trim();

      if (url && !isValidUrl(url)) {
        $field.addClass("error");
      } else {
        $field.removeClass("error");
      }
    }

    validateEmail(e) {
      const $field = $(this);
      const email = $field.val().trim();

      if (email && !isValidEmail(email)) {
        $field.addClass("error");
      } else {
        $field.removeClass("error");
      }
    }

    initAutoSave() {
      let autoSaveTimer;

      $("input, textarea, select").on(
        "input change",
        function () {
          clearTimeout(autoSaveTimer);
          autoSaveTimer = setTimeout(() => {
            this.autoSave();
          }, 30000); // Auto-save every 30 seconds
        }.bind(this)
      );
    }

    autoSave() {
      // WordPress already has auto-save functionality
      // This is just a placeholder for custom auto-save logic
      if (wp.autosave) {
        wp.autosave.server.triggerSave();
      }
    }

    initCharts() {
      // Initialize dashboard charts if Chart.js is available
      if (typeof Chart !== "undefined" && $("#events-chart").length) {
        this.initEventsChart();
      }
    }

    initEventsChart() {
      const ctx = document.getElementById("events-chart").getContext("2d");

      // This would be populated with real data from PHP
      const chartData = {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        datasets: [
          {
            label: "Events Added",
            data: [12, 19, 3, 5, 2, 3],
            borderColor: "rgb(59, 130, 246)",
            backgroundColor: "rgba(59, 130, 246, 0.1)",
            tension: 0.4,
          },
        ],
      };

      new Chart(ctx, {
        type: "line",
        data: chartData,
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "top",
            },
          },
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    }

    initBulkActions() {
      $("#doaction, #doaction2").click(function (e) {
        const action = $(this).prev("select").val();

        if (action === "delete") {
          if (
            !confirm("Are you sure you want to delete the selected events?")
          ) {
            e.preventDefault();
          }
        }
      });
    }
  }

  // Utility functions
  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }

  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Initialize when document is ready
  $(document).ready(function () {
    new AIEventsAdmin();

    // Restore active tab from localStorage
    const activeTab = localStorage.getItem("ai-events-active-tab");
    if (activeTab) {
      $('.nav-tab[href="' + activeTab + '"]').click();
    }
  });
})(jQuery);
