@extends('layouts.master')

@section('content')
<div class="app-shell" x-data="reportDetailModal()">
    <div class="app-grid">
        <x-sidebar />

        <main class="app-main fade-in">
            <x-app-header />

            <section class="glass p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="eyebrow">Progress Report</p>
                        <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Student learning progress</h2>
                    </div>

                    <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-3 sm:grid-cols-[160px_260px_auto]">
                        <div>
                            <label for="class" class="form-label">Class</label>
                            <select id="class" name="class" class="form-input py-2.5">
                                <option value="">All classes</option>
                                @foreach ($classOptions as $class)
                                    <option value="{{ $class }}" @selected($selectedClass === $class)>Class {{ $class }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="course" class="form-label">Content</label>
                            <select id="course" name="course" class="form-input py-2.5">
                                <option value="0">All contents</option>
                                @foreach ($allCourses as $course)
                                    <option value="{{ $course->id_course }}" @selected($selectedCourse === $course->id_course)>
                                        {{ $course->course_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn-primary px-4 py-2.5">Filter</button>
                            <a href="{{ route('admin.reports.index') }}" class="btn-secondary px-4 py-2.5">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            @forelse ($courses as $courseIndex => $course)
                <section class="glass p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="eyebrow">Content {{ $courseIndex + 1 }}</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">{{ $course->course_title }}</h2>
                        </div>
                        <p class="text-sm text-slate-500">{{ $course->modules->count() }} modules</p>
                    </div>

                    <div class="overflow-hidden rounded-[24px] border border-slate-200">
                        <div class="grid grid-cols-[minmax(0,1fr)_140px_220px] gap-4 border-b border-slate-200 bg-slate-50/90 px-5 py-3 text-xs font-medium uppercase tracking-widest text-slate-400 max-lg:hidden">
                            <span>Student</span>
                            <span>Class</span>
                            <span>Content Progress</span>
                        </div>

                        <div class="divide-y divide-slate-100 bg-white">
                            @forelse ($reports as $report)
                                @php
                                    $courseReport = $report['courses']->first(fn ($item) => $item['course']->id_course === $course->id_course);
                                    $student = $report['student'];
                                    $detail = [
                                        'identity' => $student->identity_id,
                                        'name' => $student->name,
                                        'class' => $student->getAttribute('class') ? 'Class ' . $student->getAttribute('class') : '-',
                                        'course' => $course->course_title,
                                        'contentPercent' => $courseReport['percent'] ?? 0,
                                        'modules' => ($courseReport['modules'] ?? collect())->map(function ($moduleReport, $moduleIndex) {
                                            return [
                                                'number' => $moduleIndex + 1,
                                                'title' => $moduleReport['module']->title,
                                                'percent' => $moduleReport['percent'],
                                                'status' => $moduleReport['status'],
                                                'statusLabel' => match ($moduleReport['status']) {
                                                    'completed' => 'Completed',
                                                    'in_progress' => 'In progress',
                                                    default => 'Not started',
                                                },
                                                'quiz' => $moduleReport['quiz_correct'] . '/' . $moduleReport['quiz_total'],
                                                'lab' => $moduleReport['lab_total'] > 0 ? $moduleReport['lab_correct'] . '/' . $moduleReport['lab_total'] : null,
                                            ];
                                        })->values(),
                                    ];
                                @endphp
                                <button
                                    type="button"
                                    class="grid w-full gap-4 px-5 py-4 text-left transition hover:bg-slate-50 lg:grid-cols-[minmax(0,1fr)_140px_220px] lg:items-center"
                                    x-on:click="openDetail(@js($detail))"
                                >
                                    <span class="min-w-0">
                                        <span class="block font-semibold text-slate-950">{{ $student->name }}</span>
                                        <span class="mt-1 block truncate text-sm text-slate-500">{{ $student->identity_id }}</span>
                                    </span>

                                    <span class="text-sm text-slate-600">{{ $student->getAttribute('class') ? 'Class ' . $student->getAttribute('class') : '-' }}</span>

                                    <span class="flex items-center gap-3">
                                        <span class="w-11 shrink-0 font-semibold text-slate-950">{{ $courseReport['percent'] ?? 0 }}%</span>
                                        <span class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                            <span class="block h-full rounded-full bg-indigo-500" style="width: {{ max(0, min(100, $courseReport['percent'] ?? 0)) }}%"></span>
                                        </span>
                                    </span>
                                </button>
                            @empty
                                <div class="px-5 py-10 text-center text-sm text-slate-500">
                                    No students match the selected filters.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            @empty
                <section class="glass p-10 text-center text-slate-500">
                    No contents are available to report.
                </section>
            @endforelse

            <div
                x-cloak
                x-show="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
                role="dialog"
                aria-modal="true"
                x-on:keydown.escape.window="closeDetail()"
            >
                <div class="absolute inset-0 bg-slate-950/45" x-on:click="closeDetail()" x-transition.opacity></div>

                <div class="relative z-10 flex max-h-[86vh] w-full max-w-2xl flex-col overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-xl shadow-slate-950/10" x-transition>
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <div class="min-w-0">
                            <p class="eyebrow" x-text="detail.course"></p>
                            <h3 class="mt-2 truncate font-display text-xl tracking-[-0.03em] text-slate-950" x-text="detail.name"></h3>
                            <p class="mt-1 text-sm text-slate-500">
                                <span x-text="detail.identity"></span>
                                <span class="mx-2 text-slate-300">/</span>
                                <span x-text="detail.class"></span>
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-950"
                            x-on:click="closeDetail()"
                            aria-label="Close report detail"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-5">
                        <div class="mb-5 rounded-[16px] border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-700">Content Progress</p>
                                <p class="font-semibold text-slate-950" x-text="detail.contentPercent + '%'"></p>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white">
                                <div class="h-full rounded-full bg-indigo-500" :style="`width: ${Math.max(0, Math.min(100, detail.contentPercent || 0))}%`"></div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <template x-for="module in detail.modules" :key="module.number">
                                <div class="rounded-[16px] border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400" x-text="`Module ${module.number}`"></p>
                                            <h4 class="mt-2 truncate text-base font-semibold text-slate-950" x-text="module.title"></h4>
                                        </div>
                                        <span
                                            class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': module.status === 'completed',
                                                'bg-blue-100 text-blue-700': module.status === 'in_progress',
                                                'bg-slate-100 text-slate-600': module.status !== 'completed' && module.status !== 'in_progress',
                                            }"
                                            x-text="module.statusLabel"
                                        ></span>
                                    </div>

                                    <div class="mt-4">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-sm text-slate-500">Progress</p>
                                            <p class="font-semibold text-slate-950" x-text="module.percent + '%'"></p>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full"
                                                :class="module.percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500'"
                                                :style="`width: ${Math.max(0, Math.min(100, module.percent || 0))}%`"
                                            ></div>
                                        </div>
                                    </div>

                                    <p class="mt-3 text-xs leading-5 text-slate-500">
                                        Quiz <span x-text="module.quiz"></span>
                                        <template x-if="module.lab">
                                            <span> &middot; Lab <span x-text="module.lab"></span></span>
                                        </template>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function reportDetailModal() {
        return {
            isOpen: false,
            detail: {
                identity: '',
                name: '',
                class: '',
                course: '',
                contentPercent: 0,
                modules: [],
            },
            openDetail(detail) {
                this.detail = detail;
                this.isOpen = true;
            },
            closeDetail() {
                this.isOpen = false;
            },
        };
    }
</script>
@endpush
