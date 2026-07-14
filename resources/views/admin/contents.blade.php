@extends('layouts.master')

@section('content')
@php
$courseCount = $courses->count();
$moduleCount = $courses->sum(fn ($course) => $course->modules->count());
$quizCount = $questionCount ?? 0;
$labCount = $labQuestionCount ?? 0;
$createCourseOpen = old('form_scope') === 'course_create';
@endphp

<div
    class="min-h-screen"
    x-data="{
        deleteModalOpen: false,
        deleteFormAction: '',
        deleteItemLabel: '',
        openDeleteModal(action, label) {
            this.deleteFormAction = action;
            this.deleteItemLabel = label;
            this.deleteModalOpen = true;
        },
    }">
    <div class="app-shell">
        <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                @if ($errors->any())
                <div class="notice-danger">
                    Validation failed. Check the form fields and try again.
                </div>
                @endif

                <section
                    x-data="{ createCourseOpen: @js($createCourseOpen) }"
                    class="glass p-6 space-y-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="eyebrow">Content Structure</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Courses, module, quiz, and lab</h2>
                        </div>

                        <button
                            type="button"
                            @click="createCourseOpen = true"
                            class="btn-primary">
                            Add Course
                        </button>
                    </div>

                    <template x-teleport="body">
                        <div
                            x-show="createCourseOpen"
                            x-cloak
                            x-transition:enter="ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @keydown.escape.window="createCourseOpen = false"
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                            <div
                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                @click="createCourseOpen = false"></div>

                            <div
                                x-show="createCourseOpen"
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                <div class="mb-5 flex items-start justify-between gap-4">
                                    <div>
                                        <p class="eyebrow">Add Course</p>
                                        <h3 class="font-display text-xl text-slate-900">Create practicum course</h3>
                                    </div>

                                    <button
                                        type="button"
                                        @click="createCourseOpen = false"
                                        class="btn-secondary px-3 py-2 text-xs">
                                        Close
                                    </button>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('admin.contents.course.store') }}"
                                    class="grid gap-4 md:grid-cols-2">
                                    @csrf
                                    <input type="hidden" name="form_scope" value="course_create">

                                    <div>
                                        <label class="form-label">Course Title</label>
                                        <input
                                            type="text"
                                            name="course_title"
                                            value="{{ $createCourseOpen ? old('course_title') : '' }}"
                                            class="form-input">
                                        @if ($createCourseOpen)
                                        @error('course_title')
                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                        @enderror
                                        @endif
                                    </div>

                                    <div>
                                        <label class="form-label">Docker Image</label>

                                        <select
                                            name="docker_image"
                                            class="form-input">

                                            <option value="">Select Docker Image</option>

                                            <option value="python:3.14.6"
                                                {{ old('docker_image') === 'python:3.14.6' ? 'selected' : '' }}>
                                                python:3.14.6
                                            </option>

                                            <option value="mariadb:10.11.18"
                                                {{ old('docker_image') === 'mariadb:10.11.18' ? 'selected' : '' }}>
                                                mariadb:10.11.18
                                            </option>

                                        </select>

                                        @if ($createCourseOpen)
                                        @error('docker_image')
                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                        @enderror
                                        @endif
                                    </div>

                                    <div class="md:col-span-2 flex justify-end gap-3">
                                        <button
                                            type="button"
                                            @click="createCourseOpen = false"
                                            class="btn-secondary px-5 py-2.5">
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            class="btn-primary px-5 py-2.5">
                                            Save Course
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                </section>

                <section class="space-y-6">
                    @forelse ($courses as $course)
                    @php
                    $courseHasContext = (int) old('course_context_id') === $course->id_course;
                    $courseEditOpen = old('form_scope') === 'course_edit' && $courseHasContext;
                    $moduleCreateOpen = old('form_scope') === 'module_create' && $courseHasContext;
                    $courseOpen = $courseHasContext || $courseEditOpen || $moduleCreateOpen;
                    $isMysqlCourse = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($course->docker_image), 'mysql');
                    @endphp

                    <div
                        x-data="{
                                storageKey: 'containearn.admin.course.{{ $course->id_course }}.hidden',
                                courseHidden: true,
                                courseEditOpen: @js($courseEditOpen),
                                moduleCreateOpen: @js($moduleCreateOpen),
                                init() {
                                    this.courseHidden = localStorage.getItem(this.storageKey) !== 'false';

                                    if (@js($courseOpen)) {
                                        this.courseHidden = false;
                                    }
                                },
                                toggleCourse() {
                                    this.courseHidden = ! this.courseHidden;
                                    localStorage.setItem(this.storageKey, this.courseHidden ? 'true' : 'false');
                                },
                                showCourse() {
                                    this.courseHidden = false;
                                    localStorage.setItem(this.storageKey, 'false');
                                },
                            }"
                        class="glass p-6 space-y-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                        Course #{{ $loop->iteration }}
                                    </span>
                                    <span class="chip">
                                        {{ $course->modules_count }} module
                                    </span>
                                </div>
                                <h3 class="mt-4 font-display text-3xl tracking-[-0.04em] text-slate-900">{{ $course->course_title }}</h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Docker image:
                                    <span class="font-medium text-slate-700">{{ $course->docker_image }}</span>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="toggleCourse()"
                                    class="btn-secondary px-4 py-2 text-xs uppercase tracking-[0.2em]">
                                    <span x-text="courseHidden ? 'Show Details' : 'Hide Details'"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="showCourse(); moduleCreateOpen = true; courseEditOpen = false"
                                    class="btn-secondary px-4 py-2 text-xs">
                                    Add Module
                                </button>
                                <button
                                    type="button"
                                    @click="showCourse(); courseEditOpen = true; moduleCreateOpen = false"
                                    class="btn-secondary px-4 py-2 text-xs">
                                    Edit Course
                                </button>
                                <button
                                    type="button"
                                    @click="openDeleteModal(@js(route('admin.contents.course.destroy', $course)), @js('course &quot;' . $course->course_title . '&quot;'))"
                                    class="inline-flex items-center justify-center rounded-[14px] bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-500">
                                    Delete
                                </button>
                            </div>
                        </div>

                        <template x-teleport="body">
                            <div
                                x-show="courseEditOpen"
                                x-cloak
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="courseEditOpen = false"
                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                <div
                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                    @click="courseEditOpen = false"></div>

                                <div
                                    x-show="courseEditOpen"
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                    <div class="mb-5 flex items-start justify-between gap-4">
                                        <div>
                                            <p class="eyebrow">Edit Course</p>
                                            <h3 class="font-display text-xl text-slate-900">{{ $course->course_title }}</h3>
                                        </div>

                                        <button
                                            type="button"
                                            @click="courseEditOpen = false"
                                            class="btn-secondary px-3 py-2 text-xs">
                                            Close
                                        </button>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.contents.course.update', $course) }}"
                                        class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="form_scope" value="course_edit">
                                        <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">

                                        <div>
                                            <label class="form-label">Course Title</label>
                                            <input
                                                type="text"
                                                name="course_title"
                                                value="{{ $courseEditOpen ? old('course_title', $course->course_title) : $course->course_title }}"
                                                class="form-input">
                                            @if ($courseEditOpen)
                                            @error('course_title')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div>
                                            <label class="form-label">Docker Image</label>
                                            <input
                                                type="text"
                                                name="docker_image"
                                                value="{{ $courseEditOpen ? old('docker_image', $course->docker_image) : $course->docker_image }}"
                                                class="form-input">
                                            @if ($courseEditOpen)
                                            @error('docker_image')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div class="md:col-span-2 flex justify-end gap-3">
                                            <button
                                                type="button"
                                                @click="courseEditOpen = false"
                                                class="btn-secondary px-5 py-2.5">
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn-primary px-5 py-2.5">
                                                Update Course
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>

                        <template x-teleport="body">
                            <div
                                x-show="moduleCreateOpen"
                                x-cloak
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="moduleCreateOpen = false"
                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                <div
                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                    @click="moduleCreateOpen = false"></div>

                                <div
                                    x-show="moduleCreateOpen"
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                    <div class="mb-5 flex items-start justify-between gap-4">
                                        <div>
                                            <p class="eyebrow">Add Module</p>
                                            <h3 class="font-display text-xl text-slate-900">{{ $course->course_title }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">Create a module for this course.</p>
                                        </div>

                                        <button
                                            type="button"
                                            @click="moduleCreateOpen = false"
                                            class="btn-secondary px-3 py-2 text-xs">
                                            Close
                                        </button>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.contents.module.store', $course) }}"
                                        enctype="multipart/form-data"
                                        class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        <input type="hidden" name="form_scope" value="module_create">
                                        <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">

                                        <div>
                                            <label class="form-label">Module Title</label>
                                            <input
                                                type="text"
                                                name="title"
                                                value="{{ $moduleCreateOpen ? old('title') : '' }}"
                                                class="form-input">
                                            @if ($moduleCreateOpen)
                                            @error('title')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div>
                                            <label class="form-label">Time Limit (Minutes)</label>
                                            <input
                                                type="number"
                                                min="1"
                                                max="1440"
                                                name="time_limit"
                                                value="{{ $moduleCreateOpen ? old('time_limit', 60) : 60 }}"
                                                class="form-input">
                                            @if ($moduleCreateOpen)
                                            @error('time_limit')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="form-label">Description</label>
                                            <textarea
                                                name="description"
                                                rows="4"
                                                class="form-input">{{ $moduleCreateOpen ? old('description') : '' }}</textarea>
                                            @if ($moduleCreateOpen)
                                            @error('description')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="form-label">Materi PDF</label>
                                            <input
                                                type="file"
                                                name="material_pdf"
                                                accept="application/pdf,.pdf"
                                                class="form-input">
                                            @if ($moduleCreateOpen)
                                            @error('material_pdf')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="form-label">{{ $isMysqlCourse ? 'Schema Database' : 'External File' }}</label>
                                            <input
                                                type="file"
                                                name="file_exe"
                                                accept=".sql,.txt,.py,.zip"
                                                class="form-input">
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $isMysqlCourse ? 'Upload file .sql untuk membuat tabel dan seed data awal module.' : 'Opsional untuk kebutuhan file eksternal module di runtime berikutnya.' }}
                                            </p>
                                            @if ($moduleCreateOpen)
                                            @error('file_exe')
                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                            @enderror
                                            @endif
                                        </div>

                                        <div class="md:col-span-2 flex justify-end gap-3">
                                            <button
                                                type="button"
                                                @click="moduleCreateOpen = false"
                                                class="btn-secondary px-5 py-2.5">
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                class="btn-primary px-5 py-2.5">
                                                Save Module
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>

                        <div
                            x-show="! courseHidden"
                            x-cloak
                            x-transition:enter="ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="space-y-4">
                            @forelse ($course->modules as $module)
                            @php
                            $moduleHasContext = (int) old('module_context_id') === $module->id_module;
                            $moduleEditOpen = old('form_scope') === 'module_edit' && $moduleHasContext;
                            $questionCreateOpen = old('form_scope') === 'quiz_create' && $moduleHasContext;
                            $labCreateOpen = old('form_scope') === 'lab_create' && $moduleHasContext;
                            $moduleOpen = $moduleHasContext || $moduleEditOpen || $questionCreateOpen || $labCreateOpen;
                            @endphp

                            <div
                                x-data="{
                                            storageKey: 'containearn.admin.module.{{ $module->id_module }}.open',
                                            open: false,
                                            moduleEditOpen: @js($moduleEditOpen),
                                            questionCreateOpen: @js($questionCreateOpen),
                                            labCreateOpen: @js($labCreateOpen),
                                            init() {
                                                this.open = localStorage.getItem(this.storageKey) === 'true';

                                                if (@js($moduleOpen)) {
                                                    this.open = true;
                                                }
                                            },
                                            toggleModule() {
                                                this.open = ! this.open;
                                                localStorage.setItem(this.storageKey, this.open ? 'true' : 'false');
                                            },
                                            showModule() {
                                                this.open = true;
                                                localStorage.setItem(this.storageKey, 'true');
                                            },
                                        }"
                                class="surface-muted">
                                <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                                    <button
                                        type="button"
                                        @click="toggleModule()"
                                        class="flex-1 text-left">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                                Module #{{ $loop->iteration }}
                                            </span>
                                            <span class="chip bg-emerald-50 text-emerald-700 border-emerald-100">
                                                {{ $module->quizQuestions->count() }} quiz
                                            </span>
                                            <span class="chip bg-amber-50 text-amber-700 border-amber-100">
                                                {{ $module->labQuestions->count() }} lab
                                            </span>
                                            <span class="chip bg-amber-50 text-amber-700 border-amber-100">
                                                {{ $module->time_limit }} minutes
                                            </span>
                                        </div>
                                        <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ $module->module_title }}</h4>
                                        <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $module->description }}</p>
                                    </button>

                                    <div class="flex flex-wrap gap-2 lg:justify-end">
                                        <a
                                            @if ($module->module_pdf_path)
                                            href="{{ asset('storage/' . $module->module_pdf_path) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn-secondary px-4 py-2 text-xs">
                                            View PDF
                                        </a>
                                        @endif
                                        @if ($module->file_exe)
                                        <span class="btn-secondary px-4 py-2 text-xs">
                                            {{ $isMysqlCourse ? 'Schema uploaded' : 'File uploaded' }}
                                        </span>
                                        @endif
                                        <button
                                            type="button"
                                            @click="showModule(); questionCreateOpen = true; labCreateOpen = false; moduleEditOpen = false"
                                            class="btn-secondary px-4 py-2 text-xs">
                                            Add Quiz
                                        </button>
                                        <button
                                            type="button"
                                            @click="showModule(); labCreateOpen = true; questionCreateOpen = false; moduleEditOpen = false"
                                            class="btn-secondary px-4 py-2 text-xs">
                                            Add Lab
                                        </button>
                                        <button
                                            type="button"
                                            @click="showModule(); moduleEditOpen = true; questionCreateOpen = false; labCreateOpen = false"
                                            class="btn-secondary px-4 py-2 text-xs">
                                            Edit Module
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(@js(route('admin.contents.module.destroy', $module)), @js('module &quot;' . $module->module_title . '&quot;'))"
                                            class="inline-flex items-center justify-center rounded-[14px] bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-500">
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    class="border-t border-slate-200 px-5 py-5 space-y-4">
                                    <template x-teleport="body">
                                        <div
                                            x-show="moduleEditOpen"
                                            x-cloak
                                            x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="ease-in duration-150"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            @keydown.escape.window="moduleEditOpen = false"
                                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                            <div
                                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                @click="moduleEditOpen = false"></div>

                                            <div
                                                x-show="moduleEditOpen"
                                                x-transition:enter="ease-out duration-200"
                                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave="ease-in duration-150"
                                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-4xl overflow-y-auto p-6 shadow-2xl">
                                                <div class="mb-5 flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="eyebrow">Edit Module</p>
                                                        <h3 class="font-display text-xl text-slate-900">{{ $module->module_title }}</h3>
                                                        <p class="mt-1 text-sm text-slate-500">{{ $course->course_title }}</p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="moduleEditOpen = false"
                                                        class="btn-secondary px-3 py-2 text-xs">
                                                        Close
                                                    </button>
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.contents.module.update', $module) }}"
                                                    enctype="multipart/form-data"
                                                    class="grid gap-4 md:grid-cols-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="form_scope" value="module_edit">
                                                    <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                    <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">

                                                    <div>
                                                        <label class="form-label">Module Title</label>
                                                        <input
                                                            type="text"
                                                            name="title"
                                                            value="{{ $moduleEditOpen ? old('title', $module->module_title) : $module->module_title }}"
                                                            class="form-input">
                                                        @if ($moduleEditOpen)
                                                        @error('title')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Time Limit (Minutes)</label>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            max="1440"
                                                            name="time_limit"
                                                            value="{{ $moduleEditOpen ? old('time_limit', $module->time_limit) : $module->time_limit }}"
                                                            class="form-input">
                                                        @if ($moduleEditOpen)
                                                        @error('time_limit')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="form-label">Description</label>
                                                        <textarea
                                                            name="description"
                                                            rows="4"
                                                            class="form-input">{{ $moduleEditOpen ? old('description', $module->description) : $module->description }}</textarea>
                                                        @if ($moduleEditOpen)
                                                        @error('description')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="form-label">Materi PDF</label>
                                                        <input
                                                            type="file"
                                                            name="material_pdf"
                                                            accept="application/pdf,.pdf"
                                                            class="form-input">
                                                        @if ($module->module_pdf_path)

                                                        href="{{ asset('storage/' . $module->module_pdf_path) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="mt-2 inline-flex text-sm font-semibold text-slate-700 hover:text-slate-950">
                                                        View current PDF
                                                        </a>
                                                        @endif
                                                        @if ($moduleEditOpen)
                                                        @error('material_pdf')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="form-label">{{ $isMysqlCourse ? 'Schema Database' : 'External File' }}</label>
                                                        <input
                                                            type="file"
                                                            name="file_exe"
                                                            accept=".sql,.txt,.py,.zip"
                                                            class="form-input">
                                                        @if ($module->file_exe)
                                                        <p class="mt-2 text-sm font-semibold text-slate-700">
                                                            Current file: {{ basename($module->file_exe) }}
                                                        </p>
                                                        @endif
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            {{ $isMysqlCourse ? 'File ini dijalankan ulang sebelum setiap submit SQL mahasiswa.' : 'File eksternal opsional untuk kebutuhan runtime module.' }}
                                                        </p>
                                                        @if ($moduleEditOpen)
                                                        @error('file_exe')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="md:col-span-2 flex justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            @click="moduleEditOpen = false"
                                                            class="btn-secondary px-5 py-2.5">
                                                            Cancel
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            class="btn-primary px-5 py-2.5">
                                                            Update Module
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-teleport="body">
                                        <div
                                            x-show="questionCreateOpen"
                                            x-cloak
                                            x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="ease-in duration-150"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            @keydown.escape.window="questionCreateOpen = false"
                                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                            <div
                                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                @click="questionCreateOpen = false"></div>

                                            <div
                                                x-show="questionCreateOpen"
                                                x-transition:enter="ease-out duration-200"
                                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave="ease-in duration-150"
                                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                                <div class="mb-5 flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="eyebrow">Add Quiz</p>
                                                        <h3 class="font-display text-xl text-slate-900">{{ $module->module_title }}</h3>
                                                        <p class="mt-1 text-sm text-slate-500">Create a multiple choice question for this module.</p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="questionCreateOpen = false"
                                                        class="btn-secondary px-3 py-2 text-xs">
                                                        Close
                                                    </button>
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.contents.questions.store', $module) }}"
                                                    class="grid gap-4">
                                                    @csrf
                                                    <input type="hidden" name="form_scope" value="quiz_create">
                                                    <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                    <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">

                                                    <div>
                                                        <label class="form-label">Question</label>
                                                        <textarea
                                                            name="question"
                                                            rows="3"
                                                            class="form-input">{{ $questionCreateOpen ? old('question') : '' }}</textarea>
                                                        @if ($questionCreateOpen)
                                                        @error('question')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="grid gap-3 md:grid-cols-2">
                                                        @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                                                        <div class="surface-subtle px-4 py-3">
                                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                                <label class="form-label mb-0">Option {{ $label }}</label>
                                                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                                                    <input type="radio" name="correct_option" value="{{ $key }}" class="h-4 w-4 border-slate-300 text-slate-900" @checked(old('correct_option', 'a' )===$key)>
                                                                    Correct
                                                                </label>
                                                            </div>
                                                            <input
                                                                type="text"
                                                                name="option_{{ $key }}"
                                                                value="{{ $questionCreateOpen ? old('option_' . $key) : '' }}"
                                                                class="form-input">
                                                            @if ($questionCreateOpen)
                                                            @error('option_' . $key)
                                                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                            @enderror
                                                            @endif
                                                        </div>
                                                        @endforeach
                                                        @if ($questionCreateOpen)
                                                        @error('correct_option')
                                                        <p class="text-xs text-rose-500 md:col-span-2">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div class="flex justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            @click="questionCreateOpen = false"
                                                            class="btn-secondary px-5 py-2.5">
                                                            Cancel
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            class="btn-primary px-5 py-2.5">
                                                            Save Quiz
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-teleport="body">
                                        <div
                                            x-show="labCreateOpen"
                                            x-cloak
                                            x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="ease-in duration-150"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            @keydown.escape.window="labCreateOpen = false"
                                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                            <div
                                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                @click="labCreateOpen = false"></div>

                                            <div
                                                x-show="labCreateOpen"
                                                x-transition:enter="ease-out duration-200"
                                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave="ease-in duration-150"
                                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                                <div class="mb-5 flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="eyebrow">Add Lab</p>
                                                        <h3 class="font-display text-xl text-slate-900">{{ $module->module_title }}</h3>
                                                        <p class="mt-1 text-sm text-slate-500">
                                                            {{ $isMysqlCourse ? 'Create a SQL practice question and validation config.' : 'Create a coding practice question and expected output.' }}
                                                        </p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        @click="labCreateOpen = false"
                                                        class="btn-secondary px-3 py-2 text-xs">
                                                        Close
                                                    </button>
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.contents.lab-questions.store', $module) }}"
                                                    class="grid gap-4"
                                                    @if ($isMysqlCourse) x-data="{ sqlMode: @js(old('sql_mode', 'direct_result')) }" @endif>
                                                    @csrf
                                                    <input type="hidden" name="form_scope" value="lab_create">
                                                    <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                    <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">

                                                    <div>
                                                        <label class="form-label">Lab Question</label>
                                                        <textarea
                                                            name="question"
                                                            rows="4"
                                                            class="form-input">{{ $labCreateOpen ? old('question') : '' }}</textarea>
                                                        @if ($labCreateOpen)
                                                        @error('question')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    @if ($isMysqlCourse)
                                                    <div>
                                                        <label class="form-label">SQL Check Mode</label>
                                                        <select name="sql_mode" class="form-input" x-model="sqlMode">
                                                            <option value="direct_result" @selected(old('sql_mode', 'direct_result' )==='direct_result' )>Direct result</option>
                                                            <option value="validation_query" @selected(old('sql_mode')==='validation_query' )>Validation query</option>
                                                        </select>
                                                        @if ($labCreateOpen)
                                                        @error('sql_mode')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Order Sensitive</label>
                                                        <input type="hidden" name="order_sensitive" value="0">
                                                        <label class="inline-flex min-h-[46px] items-center gap-2 rounded-[14px] border border-slate-200 px-3 text-sm font-semibold text-slate-700">
                                                            <input type="checkbox" name="order_sensitive" value="1" class="h-4 w-4 border-slate-300 text-slate-900" @checked((bool) old('order_sensitive', true))>
                                                            Check row order
                                                        </label>
                                                    </div>

                                                    <div x-show="sqlMode === 'validation_query'" x-cloak>
                                                        <label class="form-label">Validation Query</label>
                                                        <textarea
                                                            name="validation_query"
                                                            rows="4"
                                                            placeholder="SELECT COUNT(*) AS table_exists FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'logs'"
                                                            class="form-input font-mono">{{ $labCreateOpen ? old('validation_query') : '' }}</textarea>
                                                        @if ($labCreateOpen)
                                                        @error('validation_query')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <label class="form-label">Expected Result</label>
                                                        <textarea
                                                            name="output"
                                                            rows="6"
                                                            placeholder="name,score&#10;Dewi,95&#10;Budi,90&#10;Siti,85"
                                                            class="form-input font-mono">{{ $labCreateOpen ? old('output') : '' }}</textarea>
                                                        <p class="mt-1 text-xs text-slate-500">Gunakan format CSV sederhana. Baris pertama adalah nama kolom.</p>
                                                        @if ($labCreateOpen)
                                                        @error('output')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>
                                                    @else
                                                    <div>
                                                        <label class="form-label">Expected Output</label>
                                                        <textarea
                                                            name="output"
                                                            rows="4"
                                                            class="form-input font-mono">{{ $labCreateOpen ? old('output') : '' }}</textarea>
                                                        @if ($labCreateOpen)
                                                        @error('output')
                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                        @enderror
                                                        @endif
                                                    </div>
                                                    @endif

                                                    <div class="flex justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            @click="labCreateOpen = false"
                                                            class="btn-secondary px-5 py-2.5">
                                                            Cancel
                                                        </button>
                                                        <button
                                                            type="submit"
                                                            class="btn-primary px-5 py-2.5">
                                                            Save Lab
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="grid gap-5 xl:grid-cols-2">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="eyebrow">Quiz Questions</p>
                                                    <h5 class="mt-1 font-semibold text-slate-900">Multiple choice</h5>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="showModule(); questionCreateOpen = true; labCreateOpen = false"
                                                    class="btn-secondary px-4 py-2 text-xs">
                                                    Add Quiz
                                                </button>
                                            </div>
                                            @forelse ($module->quizQuestions as $question)
                                            @php
                                            $questionHasContext = (int) old('question_context_id') === $question->id_quiz;
                                            $questionEditOpen = old('form_scope') === 'quiz_edit' && $questionHasContext;
                                            $options = ['a' => $question->option_a, 'b' => $question->option_b, 'c' => $question->option_c, 'd' => $question->option_d];
                                            @endphp

                                            <div
                                                x-data="{ questionEditOpen: @js($questionEditOpen) }"
                                                class="surface-subtle px-4 py-4">
                                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="space-y-2 min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                                                Quiz #{{ $loop->iteration }}
                                                            </span>
                                                            <span class="chip bg-emerald-50 text-emerald-700 border-emerald-100">Answer {{ strtoupper($question->correct_option) }}</span>
                                                        </div>
                                                        <p class="text-sm font-medium leading-6 text-slate-800">{{ $question->question }}</p>
                                                    </div>

                                                    <div class="w-full space-y-3 lg:max-w-md">
                                                        <div class="grid gap-2">
                                                            @foreach ($options as $key => $option)
                                                            <p class="rounded-[14px] border px-3 py-2 text-sm {{ $question->correct_option === $key ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600' }}">
                                                                <span class="font-semibold">{{ strtoupper($key) }}.</span> {{ $option }}
                                                            </p>
                                                            @endforeach
                                                        </div>

                                                        <div class="flex flex-wrap justify-end gap-2">
                                                            <button
                                                                type="button"
                                                                @click="questionEditOpen = true"
                                                                class="btn-secondary px-4 py-2 text-xs">
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="openDeleteModal(@js(route('admin.contents.questions.destroy', $question)), @js('quiz #' . $loop->iteration . ' in &quot;' . $module->module_title . '&quot;'))"
                                                                class="inline-flex items-center justify-center rounded-[14px] bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-500">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <template x-teleport="body">
                                                    <div
                                                        x-show="questionEditOpen"
                                                        x-cloak
                                                        x-transition:enter="ease-out duration-200"
                                                        x-transition:enter-start="opacity-0"
                                                        x-transition:enter-end="opacity-100"
                                                        x-transition:leave="ease-in duration-150"
                                                        x-transition:leave-start="opacity-100"
                                                        x-transition:leave-end="opacity-0"
                                                        @keydown.escape.window="questionEditOpen = false"
                                                        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                                        <div
                                                            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                            @click="questionEditOpen = false"></div>

                                                        <div
                                                            x-show="questionEditOpen"
                                                            x-transition:enter="ease-out duration-200"
                                                            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                            x-transition:leave="ease-in duration-150"
                                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                            class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-4xl overflow-y-auto p-6 shadow-2xl">
                                                            <div class="mb-5 flex items-start justify-between gap-4">
                                                                <div>
                                                                    <p class="eyebrow">Edit Quiz</p>
                                                                    <h3 class="font-display text-xl text-slate-900">Quiz #{{ $loop->iteration }}</h3>
                                                                    <p class="mt-1 text-sm text-slate-500">{{ $module->module_title }}</p>
                                                                </div>

                                                                <button
                                                                    type="button"
                                                                    @click="questionEditOpen = false"
                                                                    class="btn-secondary px-3 py-2 text-xs">
                                                                    Close
                                                                </button>
                                                            </div>

                                                            <form
                                                                method="POST"
                                                                action="{{ route('admin.contents.questions.update', $question) }}"
                                                                class="grid gap-4">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="form_scope" value="quiz_edit">
                                                                <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                                <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">
                                                                <input type="hidden" name="question_context_id" value="{{ $question->id_quiz }}">

                                                                <div>
                                                                    <label class="form-label">Question</label>
                                                                    <textarea
                                                                        name="question"
                                                                        rows="3"
                                                                        class="form-input">{{ $questionEditOpen ? old('question', $question->question) : $question->question }}</textarea>
                                                                    @if ($questionEditOpen)
                                                                    @error('question')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>

                                                                <div class="grid gap-3 md:grid-cols-2">
                                                                    @foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                                                                    <div class="surface-subtle px-4 py-3">
                                                                        <div class="mb-2 flex items-center justify-between gap-3">
                                                                            <label class="form-label mb-0">Option {{ $label }}</label>
                                                                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                                                                <input type="radio" name="correct_option" value="{{ $key }}" class="h-4 w-4 border-slate-300 text-slate-900" @checked(old('correct_option', $question->correct_option) === $key)>
                                                                                Correct
                                                                            </label>
                                                                        </div>
                                                                        <input
                                                                            type="text"
                                                                            name="option_{{ $key }}"
                                                                            value="{{ $questionEditOpen ? old('option_' . $key, $options[$key]) : $options[$key] }}"
                                                                            class="form-input">
                                                                        @if ($questionEditOpen)
                                                                        @error('option_' . $key)
                                                                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                        @enderror
                                                                        @endif
                                                                    </div>
                                                                    @endforeach
                                                                    @if ($questionEditOpen)
                                                                    @error('correct_option')
                                                                    <p class="text-xs text-rose-500 md:col-span-2">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>

                                                                <div class="flex justify-end gap-3">
                                                                    <button
                                                                        type="button"
                                                                        @click="questionEditOpen = false"
                                                                        class="btn-secondary px-5 py-2.5">
                                                                        Cancel
                                                                    </button>
                                                                    <button
                                                                        type="submit"
                                                                        class="btn-primary px-5 py-2.5">
                                                                        Update Quiz
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            @empty
                                            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                                                No quiz questions have been added to this module yet.
                                            </div>
                                            @endforelse
                                        </div>

                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="eyebrow">Lab Questions</p>
                                                    <h5 class="mt-1 font-semibold text-slate-900">Coding practice</h5>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="showModule(); labCreateOpen = true; questionCreateOpen = false"
                                                    class="btn-secondary px-4 py-2 text-xs">
                                                    Add Lab
                                                </button>
                                            </div>

                                            @forelse ($module->labQuestions as $labQuestion)
                                            @php
                                            $labQuestionHasContext = (int) old('question_context_id') === $labQuestion->id_lab;
                                            $labEditOpen = old('form_scope') === 'lab_edit' && $labQuestionHasContext;
                                            $sqlConfig = $isMysqlCourse ? json_decode($labQuestion->output, true) : null;
                                            $sqlExpectedRows = is_array($sqlConfig) ? ($sqlConfig['expected_result'] ?? []) : [];
                                            $sqlExpectedText = '';

                                            if ($isMysqlCourse && is_array($sqlExpectedRows) && count($sqlExpectedRows) > 0) {
                                            $headers = array_keys($sqlExpectedRows[0]);
                                            $sqlExpectedText = implode(',', $headers) . "\n" . collect($sqlExpectedRows)
                                            ->map(fn ($row) => implode(',', collect($headers)->map(fn ($header) => $row[$header] ?? '')->all()))
                                            ->implode("\n");
                                            }
                                            @endphp

                                            <div
                                                x-data="{ labEditOpen: @js($labEditOpen) }"
                                                class="surface-subtle px-4 py-4">
                                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="space-y-2 min-w-0">
                                                        <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                                            Lab #{{ $loop->iteration }}
                                                        </span>
                                                        <p class="text-sm font-medium leading-6 text-slate-800">{{ $labQuestion->question }}</p>
                                                    </div>

                                                    <div class="w-full space-y-3 lg:max-w-md">
                                                        <div>
                                                            <p class="eyebrow">{{ $isMysqlCourse ? 'SQL Validation' : 'Expected Output' }}</p>
                                                            <p class="mt-2 rounded-[14px] border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-700 whitespace-pre-wrap">{{ $isMysqlCourse ? $sqlExpectedText : $labQuestion->output }}</p>
                                                        </div>

                                                        <div class="flex flex-wrap justify-end gap-2">
                                                            <button
                                                                type="button"
                                                                @click="labEditOpen = true"
                                                                class="btn-secondary px-4 py-2 text-xs">
                                                                Edit
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="openDeleteModal(@js(route('admin.contents.lab-questions.destroy', $labQuestion)), @js('lab #' . $loop->iteration . ' in &quot;' . $module->module_title . '&quot;'))"
                                                                class="inline-flex items-center justify-center rounded-[14px] bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-500">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <template x-teleport="body">
                                                    <div
                                                        x-show="labEditOpen"
                                                        x-cloak
                                                        x-transition:enter="ease-out duration-200"
                                                        x-transition:enter-start="opacity-0"
                                                        x-transition:enter-end="opacity-100"
                                                        x-transition:leave="ease-in duration-150"
                                                        x-transition:leave-start="opacity-100"
                                                        x-transition:leave-end="opacity-0"
                                                        @keydown.escape.window="labEditOpen = false"
                                                        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center">
                                                        <div
                                                            class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                            @click="labEditOpen = false"></div>

                                                        <div
                                                            x-show="labEditOpen"
                                                            x-transition:enter="ease-out duration-200"
                                                            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                            x-transition:leave="ease-in duration-150"
                                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                            class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                                                            <div class="mb-5 flex items-start justify-between gap-4">
                                                                <div>
                                                                    <p class="eyebrow">Edit Lab</p>
                                                                    <h3 class="font-display text-xl text-slate-900">Lab #{{ $loop->iteration }}</h3>
                                                                    <p class="mt-1 text-sm text-slate-500">{{ $module->module_title }}</p>
                                                                </div>

                                                                <button
                                                                    type="button"
                                                                    @click="labEditOpen = false"
                                                                    class="btn-secondary px-3 py-2 text-xs">
                                                                    Close
                                                                </button>
                                                            </div>

                                                            <form
                                                                method="POST"
                                                                action="{{ route('admin.contents.lab-questions.update', $labQuestion) }}"
                                                                class="grid gap-4"
                                                                @if ($isMysqlCourse) x-data="{ sqlMode: @js(old('sql_mode', $sqlConfig['mode'] ?? 'direct_result')) }" @endif>
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="form_scope" value="lab_edit">
                                                                <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                                <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">
                                                                <input type="hidden" name="question_context_id" value="{{ $labQuestion->id_lab }}">

                                                                <div>
                                                                    <label class="form-label">Lab Question</label>
                                                                    <textarea
                                                                        name="question"
                                                                        rows="4"
                                                                        class="form-input">{{ $labEditOpen ? old('question', $labQuestion->question) : $labQuestion->question }}</textarea>
                                                                    @if ($labEditOpen)
                                                                    @error('question')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>

                                                                @if ($isMysqlCourse)
                                                                <div>
                                                                    <label class="form-label">SQL Check Mode</label>
                                                                    <select name="sql_mode" class="form-input" x-model="sqlMode">
                                                                        <option value="direct_result" @selected(old('sql_mode', $sqlConfig['mode'] ?? 'direct_result' )==='direct_result' )>Direct result</option>
                                                                        <option value="validation_query" @selected(old('sql_mode', $sqlConfig['mode'] ?? 'direct_result' )==='validation_query' )>Validation query</option>
                                                                    </select>
                                                                    @if ($labEditOpen)
                                                                    @error('sql_mode')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>

                                                                <div>
                                                                    <label class="form-label">Order Sensitive</label>
                                                                    <input type="hidden" name="order_sensitive" value="0">
                                                                    <label class="inline-flex min-h-[46px] items-center gap-2 rounded-[14px] border border-slate-200 px-3 text-sm font-semibold text-slate-700">
                                                                        <input type="checkbox" name="order_sensitive" value="1" class="h-4 w-4 border-slate-300 text-slate-900" @checked((bool) old('order_sensitive', $sqlConfig['order_sensitive'] ?? true))>
                                                                        Check row order
                                                                    </label>
                                                                </div>

                                                                <div x-show="sqlMode === 'validation_query'" x-cloak>
                                                                    <label class="form-label">Validation Query</label>
                                                                    <textarea
                                                                        name="validation_query"
                                                                        rows="4"
                                                                        class="form-input font-mono">{{ $labEditOpen ? old('validation_query', $sqlConfig['validation_query'] ?? '') : ($sqlConfig['validation_query'] ?? '') }}</textarea>
                                                                    @if ($labEditOpen)
                                                                    @error('validation_query')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>

                                                                <div>
                                                                    <label class="form-label">Expected Result</label>
                                                                    <textarea
                                                                        name="output"
                                                                        rows="6"
                                                                        class="form-input font-mono">{{ $labEditOpen ? old('output', $sqlExpectedText) : $sqlExpectedText }}</textarea>
                                                                    <p class="mt-1 text-xs text-slate-500">Gunakan format CSV sederhana. Baris pertama adalah nama kolom.</p>
                                                                    @if ($labEditOpen)
                                                                    @error('output')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>
                                                                @else
                                                                <div>
                                                                    <label class="form-label">Expected Output</label>
                                                                    <textarea
                                                                        name="output"
                                                                        rows="4"
                                                                        class="form-input font-mono">{{ $labEditOpen ? old('output', $labQuestion->output) : $labQuestion->output }}</textarea>
                                                                    @if ($labEditOpen)
                                                                    @error('output')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                    @enderror
                                                                    @endif
                                                                </div>
                                                                @endif

                                                                <div class="flex justify-end gap-3">
                                                                    <button
                                                                        type="button"
                                                                        @click="labEditOpen = false"
                                                                        class="btn-secondary px-5 py-2.5">
                                                                        Cancel
                                                                    </button>
                                                                    <button
                                                                        type="submit"
                                                                        class="btn-primary px-5 py-2.5">
                                                                        Update Lab
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            @empty
                                            <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                                                No lab questions have been added to this module yet.
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-sm text-slate-500">
                                No module are available for this course yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                    @empty
                    <div class="glass rounded-2xl px-6 py-10 text-center text-slate-500">
                        No practicum contents are available yet.
                    </div>
                    @endforelse
                </section>
            </main>
        </div>
    </div>

    <form method="POST" :action="deleteFormAction" x-ref="deleteForm">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-delete
        show="deleteModalOpen"
        title="Delete Content?"
        message="This action will permanently remove the selected item and its child data."
        action="$refs.deleteForm.submit()" />
</div>
@endsection