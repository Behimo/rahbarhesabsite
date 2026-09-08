<footer class="rh-footer mt-16 py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-3">
            <div>
                <h3 class="mb-3 text-lg font-bold text-white">راهبر حساب</h3>
                <p class="text-sm leading-relaxed">موسسه آموزش حسابداری و خدمات مالی و مالیاتی — خالق رهبران حسابداری</p>
            </div>
            <div>
                <h4 class="mb-3 font-semibold text-white">دسترسی سریع</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('courses.index') }}" class="hover:text-white">دوره‌ها</a></li>
                    <li><a href="{{ route('blog.index') }}" class="hover:text-white">بلاگ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white">تماس</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-3 font-semibold text-white">تماس</h4>
                @if (!empty($contact['email']))
                    <p class="text-sm">{{ $contact['email'] }}</p>
                @endif
                @if (!empty($contact['phone']))
                    <p class="text-sm">{{ $contact['phone'] }}</p>
                @endif
            </div>
        </div>
        <div class="mt-8 border-t border-slate-700 pt-6 text-center text-sm text-slate-500">
            © {{ date('Y') }} راهبر حساب — تمامی حقوق محفوظ است
        </div>
    </div>
</footer>
