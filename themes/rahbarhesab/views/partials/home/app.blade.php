<section class="rahbar-app-section" id="rahbarApp">
      <!-- بکگراند دکوراتیو -->
      <div class="app-bg-shape" aria-hidden="true"></div>

      <div class="rahbar-app-container">
        <!-- =====================================
             سمت راست - محتوا
        ====================================== -->
        <div class="rahbar-app-content">
          <div class="app-headings">
            <h2>اپلیکیشن راهبر رو نصب کن...</h2>

            <p>همه حسابدارا اینجان! تنها اپلیکیشن حسابداری ایران...</p>
          </div>

          <!-- Features -->
          <div class="app-features">
            <!-- Item 1 -->
            <div class="app-feature">
              <div class="feature-icon">
                <svg
                  viewBox="0 0 64 64"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true"
                >
                  <rect
                    x="11"
                    y="16"
                    width="42"
                    height="31"
                    rx="2"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="5"
                  />

                  <line
                    x1="19"
                    y1="25"
                    x2="46"
                    y2="25"
                    stroke="currentColor"
                    stroke-width="4"
                    stroke-linecap="round"
                  />

                  <line
                    x1="19"
                    y1="34"
                    x2="46"
                    y2="34"
                    stroke="currentColor"
                    stroke-width="4"
                    stroke-linecap="round"
                  />

                  <line
                    x1="19"
                    y1="43"
                    x2="37"
                    y2="43"
                    stroke="currentColor"
                    stroke-width="4"
                    stroke-linecap="round"
                  />

                  <line
                    x1="18"
                    y1="16"
                    x2="18"
                    y2="47"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                </svg>
              </div>

              <h3>بخشنامه ها و اخبار</h3>

              <p>بخشنامه و اخبار جدید</p>
            </div>

            <!-- Item 2 -->
            <div class="app-feature">
              <div class="feature-icon">
                <svg
                  viewBox="0 0 64 64"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true"
                >
                  <rect
                    x="10"
                    y="15"
                    width="44"
                    height="39"
                    rx="4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="5"
                  />

                  <line
                    x1="10"
                    y1="26"
                    x2="54"
                    y2="26"
                    stroke="currentColor"
                    stroke-width="5"
                  />

                  <line
                    x1="21"
                    y1="9"
                    x2="21"
                    y2="20"
                    stroke="currentColor"
                    stroke-width="5"
                    stroke-linecap="round"
                  />

                  <line
                    x1="43"
                    y1="9"
                    x2="43"
                    y2="20"
                    stroke="currentColor"
                    stroke-width="5"
                    stroke-linecap="round"
                  />

                  <circle cx="21" cy="35" r="3" fill="currentColor" />
                  <circle cx="32" cy="35" r="3" fill="currentColor" />
                  <circle cx="43" cy="35" r="3" fill="currentColor" />

                  <circle cx="21" cy="45" r="3" fill="currentColor" />
                  <circle cx="32" cy="45" r="3" fill="currentColor" />
                  <circle cx="43" cy="45" r="3" fill="currentColor" />
                </svg>
              </div>

              <h3>تقویم وظایف حسابدار</h3>

              <p>یادآور مهلت های قانونی</p>
            </div>

            <!-- Item 3 -->
            <div class="app-feature">
              <div class="feature-icon">
                <svg
                  viewBox="0 0 64 64"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true"
                >
                  <circle cx="32" cy="23" r="9" fill="currentColor" />

                  <circle cx="16" cy="27" r="7" fill="currentColor" />

                  <circle cx="48" cy="27" r="7" fill="currentColor" />

                  <path
                    d="M15 50
                                   C15 40 22 34 32 34
                                   C42 34 49 40 49 50
                                   Z"
                    fill="currentColor"
                  />

                  <path
                    d="M3 48
                                   C3 40 8 35 16 35
                                   C20 35 23 36 26 39
                                   C21 42 19 46 19 51
                                   H7
                                   C5 51 3 50 3 48Z"
                    fill="currentColor"
                  />

                  <path
                    d="M61 48
                                   C61 40 56 35 48 35
                                   C44 35 41 36 38 39
                                   C43 42 45 46 45 51
                                   H57
                                   C59 51 61 50 61 48Z"
                    fill="currentColor"
                  />
                </svg>
              </div>

              <h3>پشتیبانی تخصصی</h3>

              <p>دریافت رایگان پاسخ سوالات</p>
            </div>
          </div>

          <!-- CTA -->
          <a href="{{ $appDownloadUrl ?? '#' }}" class="app-download-button" id="appDownloadButton">
            دانلود رایگان اپلیکیشن حسابداران
          </a>
        </div>

        <!-- =====================================
             سمت چپ - تصویر اپ
        ====================================== -->
        <div class="rahbar-app-visual">
          <div class="app-image-glow"></div>

          <img
            src="{{ theme_asset('images/apkrahbar2163.webp') }}"
            alt="اپلیکیشن راهبرحساب"
            class="app-mockup-image"
          />
        </div>
      </div>
    </section>
