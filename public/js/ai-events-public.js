/**
 * AI Events Pro - Public JavaScript
 */

class AIEventsPage {
  constructor() {
    this.currentPage = 1;
    this.eventsPerPage = 12;
    this.isLoading = false;
    this.hasMore = true;
    this.currentFilters = {
      location: "",
      radius: 25,
      category: "all",
      source: "all",
      search: "",
    };

    this.init();
  }

  init() {
    this.bindEvents();
    this.initGeolocation();
    this.loadEvents();
  }

  bindEvents() {
    const searchBtn = document.getElementById("search-btn");
    const searchInput = document.getElementById("event-search");
    const applyFiltersBtn = document.getElementById("apply-filters-btn");
    const loadMoreBtn = document.getElementById("load-more-btn");
    const getLocationBtn = document.getElementById("get-location-btn");

    // Search functionality
    if (searchBtn) {
      searchBtn.addEventListener("click", () => this.handleSearch());
    }

    if (searchInput) {
      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          this.handleSearch();
        }
      });

      // Auto-search with debounce
      let searchTimeout;
      searchInput.addEventListener("input", (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.handleSearch();
        }, 500);
      });
    }

    // Filter functionality
    if (applyFiltersBtn) {
      applyFiltersBtn.addEventListener("click", () => this.applyFilters());
    }

    // Load more functionality
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener("click", () => this.loadMoreEvents());
    }

    // Get location functionality
    if (getLocationBtn) {
      getLocationBtn.addEventListener("click", () => this.getUserLocation());
    }

    // Share event functionality
    document.addEventListener("click", (e) => {
      if (e.target.closest(".share-event")) {
        this.shareEvent(e.target.closest(".share-event"));
      }
    });

    // Infinite scroll (optional)
    if (window.ai_events_public && window.ai_events_public.infinite_scroll) {
      window.addEventListener(
        "scroll",
        this.throttle(() => {
          if (
            window.innerHeight + window.scrollY >=
            document.body.offsetHeight - 1000
          ) {
            if (!this.isLoading && this.hasMore) {
              this.loadMoreEvents();
            }
          }
        }, 250)
      );
    }
  }

  initGeolocation() {
    const container = document.querySelector(".ai-events-container");
    const userLocation = container?.dataset.userLocation;

    if (userLocation && userLocation !== "") {
      this.currentFilters.location = userLocation;
      const locationInput = document.getElementById("location-filter");
      if (locationInput) {
        locationInput.value = userLocation;
      }
    }
  }

  async loadEvents(reset = true) {
    if (this.isLoading) return;

    this.isLoading = true;
    this.showLoading(true);

    if (reset) {
      this.currentPage = 1;
      this.hasMore = true;
    }

    const formData = new FormData();
    formData.append("action", "get_events");
    formData.append("nonce", ai_events_public.nonce);
    formData.append("location", this.currentFilters.location);
    formData.append("radius", this.currentFilters.radius);
    formData.append("limit", this.eventsPerPage);
    formData.append("offset", (this.currentPage - 1) * this.eventsPerPage);
    formData.append("category", this.currentFilters.category);
    formData.append("source", this.currentFilters.source);
    formData.append("search", this.currentFilters.search);

    try {
      const response = await fetch(ai_events_public.ajax_url, {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        this.renderEvents(data.data.html, reset);
        this.hasMore = data.data.has_more;
        this.updateLoadMoreButton();
        this.hideNoEventsMessage();
      } else {
        if (reset) {
          this.showNoEventsMessage();
        }
        this.hideLoadMoreButton();
      }
    } catch (error) {
      console.error("Error loading events:", error);
      this.showError("Failed to load events. Please try again.");
    } finally {
      this.isLoading = false;
      this.showLoading(false);
    }
  }

  async loadMoreEvents() {
    if (!this.hasMore || this.isLoading) return;

    this.currentPage++;
    await this.loadEvents(false);
  }

  renderEvents(html, reset = true) {
    const container = document.getElementById("events-container");
    if (!container) return;

    if (reset) {
      container.innerHTML = html;
    } else {
      container.insertAdjacentHTML("beforeend", html);
    }

    // Animate new events
    const newEvents = container.querySelectorAll(
      ".ai-event-card:not(.animated)"
    );
    newEvents.forEach((event, index) => {
      event.classList.add("animated");
      event.style.opacity = "0";
      event.style.transform = "translateY(20px)";

      setTimeout(() => {
        event.style.transition = "opacity 0.4s ease, transform 0.4s ease";
        event.style.opacity = "1";
        event.style.transform = "translateY(0)";
      }, index * 100);
    });
  }

  handleSearch() {
    const searchInput = document.getElementById("event-search");
    if (searchInput) {
      this.currentFilters.search = searchInput.value.trim();
      this.loadEvents(true);
    }
  }

  applyFilters() {
    // Get filter values
    const locationFilter = document.getElementById("location-filter");
    const radiusFilter = document.getElementById("radius-filter");
    const categoryFilter = document.getElementById("category-filter");
    const sourceFilter = document.getElementById("source-filter");

    if (locationFilter) this.currentFilters.location = locationFilter.value;
    if (radiusFilter) this.currentFilters.radius = parseInt(radiusFilter.value);
    if (categoryFilter) this.currentFilters.category = categoryFilter.value;
    if (sourceFilter) this.currentFilters.source = sourceFilter.value;

    this.loadEvents(true);
  }

  async getUserLocation() {
    const button = document.getElementById("get-location-btn");
    const locationInput = document.getElementById("location-filter");

    if (!navigator.geolocation) {
      this.showError("Geolocation is not supported by this browser.");
      return;
    }

    button.classList.add("loading");

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude } = position.coords;
          const location = await this.reverseGeocode(latitude, longitude);

          if (location) {
            this.currentFilters.location = location;
            if (locationInput) {
              locationInput.value = location;
            }
            this.loadEvents(true);
          }
        } catch (error) {
          console.error("Error getting location:", error);
          this.showError("Failed to get your location.");
        } finally {
          button.classList.remove("loading");
        }
      },
      (error) => {
        button.classList.remove("loading");
        let errorMessage = "Unable to get your location.";

        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage =
              "Location access denied. Please enable location permissions.";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = "Location information is unavailable.";
            break;
          case error.TIMEOUT:
            errorMessage = "Location request timed out.";
            break;
        }

        this.showError(errorMessage);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      }
    );
  }

  async reverseGeocode(latitude, longitude) {
    try {
      const response = await fetch(
        `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`
      );

      const data = await response.json();

      if (data.city && data.principalSubdivision) {
        return `${data.city}, ${data.principalSubdivision}`;
      } else if (data.locality) {
        return data.locality;
      }

      return null;
    } catch (error) {
      console.error("Reverse geocoding error:", error);
      return null;
    }
  }

  shareEvent(button) {
    const eventTitle = button.dataset.eventTitle;
    const eventUrl = button.dataset.eventUrl;

    if (navigator.share) {
      navigator
        .share({
          title: eventTitle,
          url: eventUrl,
        })
        .catch((err) => console.log("Error sharing:", err));
    } else {
      // Fallback: copy to clipboard
      const shareText = `Check out this event: ${eventTitle} ${eventUrl}`;

      if (navigator.clipboard) {
        navigator.clipboard.writeText(shareText).then(() => {
          this.showSuccess("Event link copied to clipboard!");
        });
      } else {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = shareText;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        this.showSuccess("Event link copied to clipboard!");
      }
    }
  }

  showLoading(show) {
    const loading = document.getElementById("events-loading");
    if (loading) {
      loading.style.display = show ? "flex" : "none";
    }
  }

  showNoEventsMessage() {
    const message = document.getElementById("no-events-message");
    if (message) {
      message.style.display = "block";
    }
  }

  hideNoEventsMessage() {
    const message = document.getElementById("no-events-message");
    if (message) {
      message.style.display = "none";
    }
  }

  updateLoadMoreButton() {
    const button = document.getElementById("load-more-btn");
    if (button) {
      if (this.hasMore) {
        button.style.display = "block";
        button.disabled = false;
        button.textContent = ai_events_public.strings.load_more;
      } else {
        button.style.display = "none";
      }
    }
  }

  hideLoadMoreButton() {
    const button = document.getElementById("load-more-btn");
    if (button) {
      button.style.display = "none";
    }
  }

  showError(message) {
    this.showNotification(message, "error");
  }

  showSuccess(message) {
    this.showNotification(message, "success");
  }

  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `ai-events-notification ${type}`;
    notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

    // Add to page
    document.body.appendChild(notification);

    // Show with animation
    setTimeout(() => notification.classList.add("show"), 100);

    // Auto-remove after 5 seconds
    const timeout = setTimeout(
      () => this.removeNotification(notification),
      5000
    );

    // Manual close
    notification
      .querySelector(".notification-close")
      .addEventListener("click", () => {
        clearTimeout(timeout);
        this.removeNotification(notification);
      });
  }

  removeNotification(notification) {
    notification.classList.add("hide");
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300);
  }

  throttle(func, limit) {
    let inThrottle;
    return function () {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  if (document.querySelector(".ai-events-container")) {
    window.aiEventsPage = new AIEventsPage();
  }
});

// Notification styles
const notificationStyles = `
<style>
.ai-events-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 400px;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.ai-events-notification.show {
    transform: translateX(0);
}

.ai-events-notification.hide {
    transform: translateX(100%);
}

.ai-events-notification.success {
    border-color: var(--success-color);
    background: #d1fae5;
    color: #065f46;
}

.ai-events-notification.error {
    border-color: var(--error-color);
    background: #fee2e2;
    color: #991b1b;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    opacity: 1;
}
</style>
`;

document.head.insertAdjacentHTML("beforeend", notificationStyles);
