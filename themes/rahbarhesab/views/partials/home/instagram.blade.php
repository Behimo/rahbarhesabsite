<section class="rahbar-instagram-section" id="rahbarInstagram">
      <div class="rahbar-instagram-container">
        <!-- =========================
             باکس اصلی اینستاگرام
        ========================== -->
        <div class="rahbar-instagram-box">
          <!-- Pattern -->
          <div class="instagram-pattern" aria-hidden="true"></div>

          <div class="instagram-content">
            <!-- Header -->
            <div class="instagram-header">
              <h2>ما رو در اینستاگرام دنبال کن</h2>

              <p>
                ما روزانه مطالب آموزشی و اخبار فوری حسابداری رو در اینستاگرام به
                اشتراک میگذاریم
              </p>
            </div>

            <!-- Features -->
            <div class="instagram-features">
              <!-- Item 1 -->
              <div class="instagram-feature">
                <span class="instagram-pin">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"></circle>
                    <path d="M12 13V21"></path>
                    <path d="M9.5 8L11 9.5L14.5 6"></path>
                  </svg>
                </span>

                <div class="instagram-feature-text">
                  <h3>نکات آموزشی</h3>

                  <p>نکات آموزشی حسابداری و مالیات</p>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="instagram-feature">
                <span class="instagram-pin">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"></circle>
                    <path d="M12 13V21"></path>
                    <path d="M9.5 8L11 9.5L14.5 6"></path>
                  </svg>
                </span>

                <div class="instagram-feature-text">
                  <h3>آخرین تخفیفات</h3>

                  <p>تخفیف‌های ویژه دوره‌ها و خدمات</p>
                </div>
              </div>

              <!-- Item 3 -->
              <div class="instagram-feature">
                <span class="instagram-pin">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="5"></circle>
                    <path d="M12 13V21"></path>
                    <path d="M9.5 8L11 9.5L14.5 6"></path>
                  </svg>
                </span>

                <div class="instagram-feature-text">
                  <h3>بخشنامه ها</h3>

                  <p>جدیدترین بخشنامه‌های مالیاتی</p>
                </div>
              </div>
            </div>

            <!-- CTA -->
            <a
              href="{{ $contact['instagram'] ?? 'https://instagram.com/rahbarhesab' }}"
              class="instagram-button"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span> اینستاگرام راهبر حساب </span>

              <span class="instagram-button-arrow">
                <svg viewBox="0 0 24 24">
                  <path d="M14 5L7 12L14 19" />
                </svg>
              </span>
            </a>
          </div>
        </div>

        <!-- =========================
             موبایل سمت چپ
        ========================== -->
        <div class="rahbar-instagram-phone">
          <div class="instagram-phone-shadow"></div>

          <img
            src="{{ theme_asset('images/instagramupdate.webp') }}"
            alt="اینستاگرام راهبر حساب"
            class="instagram-phone-image"
          />
        </div>
      </div>
    </section>
