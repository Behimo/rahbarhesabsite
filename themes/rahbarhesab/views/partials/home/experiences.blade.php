<section class="student-experience-section" id="studentExperiences">
      <div class="student-experience-container">
        <!-- Header -->
        <div class="student-experience-header">
          <h2>تجربیات دانشجویان ما</h2>

          <a href="{{ route('blog.index') }}" class="student-experience-more">
            <span>تجربیات بیشتر</span>

            <svg viewBox="0 0 24 24">
              <path d="M14 5L7 12L14 19" />
            </svg>
          </a>
        </div>

        <!-- Cards -->
        <div class="student-experience-grid">
          <!-- Card 1 -->
          <a href="#" class="student-experience-card">
            <img
              src="{{ theme_asset('images/experience-1.jpg') }}"
              alt="تجربه دانشجوی دوره VIP راهبر حساب"
            />

            <span class="student-play-button">
              <svg viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="29" />
                <path d="M27 21L45 32L27 43Z" />
              </svg>
            </span>
          </a>

          <!-- Card 2 -->
          <a href="#" class="student-experience-card">
            <img src="{{ theme_asset('images/experience-2.jpg') }}" alt="تجربه دانشجوی راهبر حساب" />

            <span class="student-play-button">
              <svg viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="29" />
                <path d="M27 21L45 32L27 43Z" />
              </svg>
            </span>
          </a>

          <!-- Card 3 -->
          <a href="#" class="student-experience-card">
            <img
              src="{{ theme_asset('images/experience-3.jpg') }}"
              alt="تجربه دانشجوی دوره اظهارنامه VIP"
            />

            <span class="student-play-button">
              <svg viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="29" />
                <path d="M27 21L45 32L27 43Z" />
              </svg>
            </span>
          </a>
        </div>
      </div>
    </section>
