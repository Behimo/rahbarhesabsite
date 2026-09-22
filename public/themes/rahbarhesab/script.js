document.addEventListener("DOMContentLoaded", () => {
  const mobileMenuButton = document.getElementById("mobileMenuButton");

  const mainNav = document.getElementById("mainNav");

  const dropdowns = document.querySelectorAll(".nav-dropdown");

  const searchInput = document.getElementById("searchInput");

  const searchButton = document.getElementById("searchButton");

  /*
    =============================
    Mobile Menu
    =============================
    */

  mobileMenuButton.addEventListener("click", () => {
    mainNav.classList.toggle("show");

    mobileMenuButton.classList.toggle("active");
  });

  /*
    =============================
    Dropdowns
    =============================
    */

  dropdowns.forEach((dropdown) => {
    const trigger = dropdown.querySelector(".dropdown-trigger");

    trigger.addEventListener("click", (event) => {
      if (window.innerWidth <= 980) {
        event.preventDefault();

        dropdowns.forEach((item) => {
          if (item !== dropdown) {
            item.classList.remove("open");
          }
        });

        dropdown.classList.toggle("open");
      }
    });
  });

  /*
    =============================
    Search
    =============================
    */

  function handleSearch() {
    const value = searchInput.value.trim();

    if (!value) {
      searchInput.focus();

      return;
    }

    const form = searchInput.closest("form");

    if (form) {
      form.submit();
      return;
    }

    const searchUrl = searchInput.getAttribute("data-search-url") || "/search";

    window.location.href =
      searchUrl + "?q=" + encodeURIComponent(value);
  }

  searchButton.addEventListener("click", handleSearch);

  searchInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      handleSearch();
    }
  });

  /*
    =============================
    Close menu on outside click
    =============================
    */

  document.addEventListener("click", (event) => {
    if (
      window.innerWidth <= 980 &&
      !mainNav.contains(event.target) &&
      !mobileMenuButton.contains(event.target)
    ) {
      mainNav.classList.remove("show");
    }
  });

  /*
    =============================
    Window Resize
    =============================
    */

  window.addEventListener("resize", () => {
    if (window.innerWidth > 980) {
      mainNav.classList.remove("show");

      dropdowns.forEach((item) => {
        item.classList.remove("open");
      });
    }
  });
});

/*Ø³Ú©Ø´Ù Ø¯ÙÙ*/
document.addEventListener("DOMContentLoaded", () => {
  const aboutSection = document.querySelector(".rahbar-about");

  const aboutCard = document.querySelector(".about-card");

  const pawn = document.querySelector(".main-pawn");

  if (!aboutSection) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          aboutCard.classList.add("show");
          pawn.classList.add("show");

          observer.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.25,
    },
  );

  observer.observe(aboutSection);
});

/*Ø³Ú©Ø´Ù Ø³ÙÙ*/

document.addEventListener("DOMContentLoaded", () => {
  const appSection = document.querySelector(".rahbar-app-section");

  if (!appSection) return;

  /* ======================================
       ÙÙØ§ÛØ´ ÙØ±Ù Ø³Ú©Ø´Ù ÙÙÚ¯Ø§Ù Ø§Ø³Ú©Ø±ÙÙ
    ====================================== */

  const appObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          appObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.18,
    },
  );

  appObserver.observe(appSection);

  /* ======================================
       Download Button
    ====================================== */

  const downloadButton = document.getElementById("appDownloadButton");

  if (downloadButton) {
    downloadButton.addEventListener("click", (event) => {
      /*
                ÙÙØªÛ ÙÛÙÚ© Ø¯Ø§ÙÙÙØ¯ Ø§Ù¾ Ø¢ÙØ§Ø¯Ù Ø¨ÙØ¯Ø
                href Ø®ÙØ¯ Ø¯Ú©ÙÙ Ø±Ø§ ØªØºÛÛØ± Ø¨Ø¯Ù.

                ÙØ«Ø§Ù:

                href="/download-app"

                Ø¯Ø± Ø¢Ù ØµÙØ±Øª Ø§ÛÙ ÙØ³ÙØª JS
                Ø±Ø§ ÙÛâØªÙØ§ÙÛ Ú©Ø§ÙÙ Ø­Ø°Ù Ú©ÙÛ.
                */

      const href = downloadButton.getAttribute("href");

      if (!href || href === "#") {
        event.preventDefault();
      }
    });
  }
});

/* Ø³Ú©Ø´Ù ÚÙØ§Ø±Ù*/

document.addEventListener("DOMContentLoaded", () => {
  const benefitsSection = document.querySelector(".rahbar-benefits");

  if (!benefitsSection) return;

  const benefitsObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          benefitsObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.2,
    },
  );

  benefitsObserver.observe(benefitsSection);
});

//Ø³Ú©Ø´Ù Ù¾ÙØ¬Ù

document.addEventListener("DOMContentLoaded", () => {
  const sliders = document.querySelectorAll(".courses-carousel-section");

  if (!sliders.length) return;

  sliders.forEach((slider) => {

  const viewport = slider.querySelector(".courses-viewport");

  const track = slider.querySelector(".courses-track");

  const cards = Array.from(slider.querySelectorAll(".course-card"));

  const prevButton = slider.querySelector(".courses-prev");

  const nextButton = slider.querySelector(".courses-next");

  let currentIndex = 0;

  let startX = 0;
  let currentX = 0;
  let isDragging = false;

  /* ============================
           ØªØ¹Ø¯Ø§Ø¯ Ú©Ø§Ø±ØªâÙØ§Û ÙØ§Ø¨Ù ÙÙØ§ÛØ´
        ============================ */

  function getVisibleCards() {
    const width = window.innerWidth;

    if (width <= 520) {
      return 1;
    }

    if (width <= 780) {
      return 2;
    }

    if (width <= 1050) {
      return 3;
    }

    return 4;
  }

  /* ============================
           Ø§ÙØ¯Ø§Ø²Ù Ø­Ø±Ú©Øª ÙØ± Ú©Ø§Ø±Øª
        ============================ */

  function getStep() {
    if (!cards.length) {
      return 0;
    }

    const cardWidth = cards[0].getBoundingClientRect().width;

    const trackStyles = window.getComputedStyle(track);

    const gap = parseFloat(trackStyles.columnGap || trackStyles.gap || 0);

    return cardWidth + gap;
  }

  /* ============================
           ÙØ­Ø¯ÙØ¯ÛØª Index
        ============================ */

  function getMaxIndex() {
    const visible = getVisibleCards();

    return Math.max(0, cards.length - visible);
  }

  /* ============================
           Update
        ============================ */

  function updateSlider(withAnimation = true) {
    const maxIndex = getMaxIndex();

    currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));

    const move = currentIndex * getStep();

    track.style.transition = withAnimation
      ? "transform .48s cubic-bezier(.22,.8,.25,1)"
      : "none";

    /*
            Track Ø¨Ù ØµÙØ±Øª LTR Ø³Ø§Ø®ØªÙ Ø´Ø¯ÙØ
            Ù¾Ø³ Ø¨Ø±Ø§Û Ø¯ÛØ¯Ù Ú©Ø§Ø±ØªâÙØ§Û Ø¨Ø¹Ø¯Û
            Ø¨Ù Ø³ÙØª ÚÙ¾ Ø­Ø±Ú©Øª ÙÛâÚ©ÙØ¯.
            */

    track.style.transform = `translateX(-${move}px)`;

    prevButton.disabled = currentIndex === 0;

    nextButton.disabled = currentIndex >= maxIndex;
  }

  /* ============================
           ARROWS
        ============================ */

  nextButton.addEventListener("click", () => {
    currentIndex++;

    updateSlider();
  });

  prevButton.addEventListener("click", () => {
    currentIndex--;

    updateSlider();
  });

  /* ============================
           MOUSE DRAG
        ============================ */

  viewport.addEventListener("mousedown", (event) => {
    isDragging = true;

    startX = event.clientX;

    currentX = startX;

    viewport.classList.add("dragging");

    track.style.transition = "none";
  });

  window.addEventListener("mousemove", (event) => {
    if (!isDragging) return;

    currentX = event.clientX;

    const diff = currentX - startX;

    const baseMove = currentIndex * getStep();

    track.style.transform = `translateX(${-baseMove + diff}px)`;
  });

  window.addEventListener("mouseup", () => {
    if (!isDragging) return;

    const diff = currentX - startX;

    isDragging = false;

    viewport.classList.remove("dragging");

    if (Math.abs(diff) > 55) {
      if (diff < 0) {
        currentIndex++;
      } else {
        currentIndex--;
      }
    }

    updateSlider();
  });

  /* ============================
           TOUCH
        ============================ */

  viewport.addEventListener(
    "touchstart",
    (event) => {
      if (!event.touches.length) {
        return;
      }

      startX = event.touches[0].clientX;

      currentX = startX;

      track.style.transition = "none";
    },
    {
      passive: true,
    },
  );

  viewport.addEventListener(
    "touchmove",
    (event) => {
      if (!event.touches.length) {
        return;
      }

      currentX = event.touches[0].clientX;

      const diff = currentX - startX;

      const baseMove = currentIndex * getStep();

      track.style.transform = `translateX(${-baseMove + diff}px)`;
    },
    {
      passive: true,
    },
  );

  viewport.addEventListener("touchend", () => {
    const diff = currentX - startX;

    if (Math.abs(diff) > 45) {
      if (diff < 0) {
        currentIndex++;
      } else {
        currentIndex--;
      }
    }

    updateSlider();
  });

  /* ============================
           RESIZE
        ============================ */

  let resizeTimer;

  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);

    resizeTimer = setTimeout(() => {
      updateSlider(false);
    }, 100);
  });

  /* ============================
           Ø¬ÙÙÚ¯ÛØ±Û Ø§Ø² Drag Ø´Ø¯Ù Ø¹Ú©Ø³
        ============================ */

  slider.querySelectorAll("img").forEach((image) => {
    image.addEventListener("dragstart", (event) => event.preventDefault());
  });

  /* Start */

  updateSlider(false);
  });
});

/*Ø³Ú©Ø´Ù Ø´Ø´Ù*/

document.addEventListener("DOMContentLoaded", () => {
  const financeSection = document.querySelector(".rahbar-finance-section");

  if (!financeSection) return;

  const financeObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          financeObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.18,
    },
  );

  financeObserver.observe(financeSection);
});

// Ø³Ú©Ø´ faq

document.addEventListener("DOMContentLoaded", () => {
  const faqSection = document.querySelector("#popularFaqs");

  if (!faqSection) return;

  const viewport = faqSection.querySelector(".faq-viewport");

  const track = faqSection.querySelector(".faq-track");

  const cards = Array.from(faqSection.querySelectorAll(".faq-card"));

  const prevButton = faqSection.querySelector(".faq-prev");

  const nextButton = faqSection.querySelector(".faq-next");

  let currentIndex = 0;

  let startX = 0;

  let currentX = 0;

  let isDragging = false;

  /* =====================================
       Visible cards
    ===================================== */

  function getVisibleCards() {
    const width = window.innerWidth;

    if (width <= 540) {
      return 1;
    }

    if (width <= 800) {
      return 2;
    }

    if (width <= 1100) {
      return 3;
    }

    return 4;
  }

  /* =====================================
       Step
    ===================================== */

  function getStep() {
    if (!cards.length) {
      return 0;
    }

    const cardWidth = cards[0].getBoundingClientRect().width;

    const styles = window.getComputedStyle(track);

    const gap = parseFloat(styles.gap || styles.columnGap || 0);

    return cardWidth + gap;
  }

  /* =====================================
       Max index
    ===================================== */

  function getMaxIndex() {
    return Math.max(0, cards.length - getVisibleCards());
  }

  /* =====================================
       Update
    ===================================== */

  function updateSlider(animate = true) {
    const maxIndex = getMaxIndex();

    currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));

    const move = currentIndex * getStep();

    track.style.transition = animate
      ? "transform .48s cubic-bezier(.22,.8,.25,1)"
      : "none";

    track.style.transform = `translateX(-${move}px)`;

    prevButton.disabled = currentIndex === 0;

    nextButton.disabled = currentIndex >= maxIndex;
  }

  /* =====================================
       Buttons
    ===================================== */

  nextButton.addEventListener("click", () => {
    currentIndex++;

    updateSlider();
  });

  prevButton.addEventListener("click", () => {
    currentIndex--;

    updateSlider();
  });

  /* =====================================
       Mouse Drag
    ===================================== */

  viewport.addEventListener("mousedown", (event) => {
    isDragging = true;

    startX = event.clientX;

    currentX = startX;

    viewport.classList.add("dragging");

    track.style.transition = "none";
  });

  window.addEventListener("mousemove", (event) => {
    if (!isDragging) return;

    currentX = event.clientX;

    const diff = currentX - startX;

    const base = currentIndex * getStep();

    track.style.transform = `translateX(${-base + diff}px)`;
  });

  window.addEventListener("mouseup", () => {
    if (!isDragging) return;

    isDragging = false;

    viewport.classList.remove("dragging");

    const diff = currentX - startX;

    if (Math.abs(diff) > 55) {
      if (diff < 0) {
        currentIndex++;
      } else {
        currentIndex--;
      }
    }

    updateSlider();
  });

  /* =====================================
       TOUCH
    ===================================== */

  viewport.addEventListener(
    "touchstart",
    (event) => {
      if (!event.touches.length) return;

      startX = event.touches[0].clientX;

      currentX = startX;

      track.style.transition = "none";
    },
    {
      passive: true,
    },
  );

  viewport.addEventListener(
    "touchmove",
    (event) => {
      if (!event.touches.length) return;

      currentX = event.touches[0].clientX;

      const diff = currentX - startX;

      const base = currentIndex * getStep();

      track.style.transform = `translateX(${-base + diff}px)`;
    },
    {
      passive: true,
    },
  );

  viewport.addEventListener("touchend", () => {
    const diff = currentX - startX;

    if (Math.abs(diff) > 45) {
      if (diff < 0) {
        currentIndex++;
      } else {
        currentIndex--;
      }
    }

    updateSlider();
  });

  /* =====================================
       Resize
    ===================================== */

  let resizeTimer;

  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);

    resizeTimer = setTimeout(() => {
      updateSlider(false);
    }, 100);
  });

  /* =====================================
       Entrance animation
    ===================================== */

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          faqSection.classList.add("is-visible");

          observer.unobserve(faqSection);
        }
      });
    },

    {
      threshold: 0.2,
    },
  );

  observer.observe(faqSection);

  updateSlider(false);
});

//Ø³Ú©Ø´Ùnews-guides-section

document.addEventListener("DOMContentLoaded", () => {
  const section = document.querySelector(".news-guides-section");

  if (!section) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          observer.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.15,
    },
  );

  observer.observe(section);
});

/*Ø³Ú©Ø´Ù rahbar-system-section*/

document.addEventListener("DOMContentLoaded", () => {
  const systemSection = document.querySelector(".rahbar-system-section");

  if (!systemSection) return;

  const systemObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          systemObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.18,
    },
  );

  systemObserver.observe(systemSection);
});

/*Ø³Ú©Ø´Ù rahbar-instagram-section*/

document.addEventListener("DOMContentLoaded", () => {
  const instagramSection = document.querySelector(".rahbar-instagram-section");

  if (!instagramSection) return;

  const instagramObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          instagramObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.17,
    },
  );

  instagramObserver.observe(instagramSection);
});

//Ø³Ú©Ø´Ù
document.addEventListener("DOMContentLoaded", () => {
  const mentorSection = document.querySelector(".mentor-section");

  if (!mentorSection) return;

  const mentorObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          mentorObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.18,
    },
  );

  mentorObserver.observe(mentorSection);
});

//Ø³Ú©Ø´Ù

document.addEventListener("DOMContentLoaded", () => {
  const experienceSection = document.querySelector(
    ".student-experience-section",
  );

  if (!experienceSection) return;

  const experienceObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");

          experienceObserver.unobserve(entry.target);
        }
      });
    },

    {
      threshold: 0.18,
    },
  );

  experienceObserver.observe(experienceSection);
});

/*Ø³Ú©Ø´Ù rahbar-partners-section*/

/*ÙÙØªØ±*/

document.addEventListener("DOMContentLoaded", () => {
  const backToTop = document.getElementById("footerBackToTop");

  if (backToTop) {
    backToTop.addEventListener("click", () => {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }
});

/* Auth - tabs, password reveal, submit feedback */
document.addEventListener('DOMContentLoaded', () => {
  const roots = document.querySelectorAll('[data-rh-auth]');
  if (!roots.length) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  roots.forEach((root) => {
    const tabs = root.querySelectorAll('[data-auth-tab]');
    const panels = root.querySelectorAll('[data-auth-panel]');

    const activateTab = (name) => {
      tabs.forEach((tab) => {
        const active = tab.getAttribute('data-auth-tab') === name;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.tabIndex = active ? 0 : -1;
      });

      panels.forEach((panel) => {
        const active = panel.getAttribute('data-auth-panel') === name;
        panel.classList.toggle('is-active', active);
        if (active) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', '');
        }
      });

      root.setAttribute('data-active-tab', name);
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        activateTab(tab.getAttribute('data-auth-tab'));
      });

      tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const list = Array.from(tabs);
        const index = list.indexOf(tab);
        let next = index;
        if (event.key === 'ArrowLeft') next = (index + 1) % list.length;
        if (event.key === 'ArrowRight') next = (index - 1 + list.length) % list.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = list.length - 1;
        list[next].focus();
        activateTab(list[next].getAttribute('data-auth-tab'));
      });
    });

    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        const wrap = button.closest('.rh-auth__secret');
        const input = wrap ? wrap.querySelector('input') : null;
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.classList.toggle('is-revealed', show);
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', show ? 'مخفی کردن رمز عبور' : 'نمایش رمز عبور');
      });
    });

    root.querySelectorAll('[data-auth-form]').forEach((form) => {
      form.addEventListener('submit', () => {
        const submit = form.querySelector('[data-auth-submit]');
        if (!submit || submit.disabled) return;

        const label = submit.querySelector('[data-auth-submit-label]');
        const spinner = submit.querySelector('[data-auth-spinner]');
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        if (label) label.textContent = 'در حال ارسال…';
        if (spinner) spinner.hidden = false;
        if (reduceMotion && spinner) spinner.style.animation = 'none';
      });
    });
  });
});
