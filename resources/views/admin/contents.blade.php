@extends('layouts.master')

@section('content')
@php
    $courseCount = $courses->count();
    $moduleCount = $courses->sum(fn ($course) => $course->modules->count());
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
    }"
>
    <div class="app-shell">
        <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />

                <header class="glass overflow-hidden p-7 sm:p-8 lg:p-10">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr),360px]">
                        <div>
                            <p class="eyebrow">Practicum Panel</p>
                            <h1 class="page-title">Practicum Contents</h1>
                        </div>
                    </div>
                </header>

                <x-alert-success />

                @if ($errors->any())
                    <div class="notice-danger">
                        Validation failed. Check the form fields and try again.
                    </div>
                @endif

                <section
                    x-data="{ createCourseOpen: @js($createCourseOpen) }"
                    class="glass p-6 space-y-5"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="eyebrow">Content Structure</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Courses, modules, and questions</h2>
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
                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                        >
                            <div
                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                @click="createCourseOpen = false"
                            ></div>

                            <div
                                x-show="createCourseOpen"
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                            >
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
                                    action="{{ route('admin.contents.courses.store') }}"
                                    class="grid gap-4 md:grid-cols-2"
                                >
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
                                        <input
                                            type="text"
                                            name="docker_image"
                                            value="{{ $createCourseOpen ? old('docker_image') : '' }}"
                                            class="form-input"
                                            placeholder="python:3.12-slim">
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
                        @endphp

                        <div
                            x-data="{
                                courseOpen: @js($courseOpen),
                                courseEditOpen: @js($courseEditOpen),
                                moduleCreateOpen: @js($moduleCreateOpen),
                            }"
                            class="glass p-6 space-y-5"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                            Course #{{ $course->id_course }}
                                        </span>
                                        <span class="chip">
                                            {{ $course->modules_count }} modules
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
                                        @click="courseOpen = !courseOpen"
                                        class="btn-secondary px-4 py-2 text-xs uppercase tracking-[0.2em]">
                                        <span x-text="courseOpen ? 'Hide Details' : 'Show Details'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="courseOpen = true; moduleCreateOpen = true; courseEditOpen = false"
                                        class="btn-secondary px-4 py-2 text-xs">
                                        Add Module
                                    </button>
                                    <button
                                        type="button"
                                        @click="courseOpen = true; courseEditOpen = true; moduleCreateOpen = false"
                                        class="btn-secondary px-4 py-2 text-xs">
                                        Edit Course
                                    </button>
                                    <button
                                        type="button"
                                        @click="openDeleteModal(@js(route('admin.contents.courses.destroy', $course)), @js('course &quot;' . $course->course_title . '&quot;'))"
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
                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                            >
                                <div
                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                    @click="courseEditOpen = false"
                                ></div>

                                <div
                                    x-show="courseEditOpen"
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                                >
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
                                        action="{{ route('admin.contents.courses.update', $course) }}"
                                        class="grid gap-4 md:grid-cols-2"
                                    >
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
                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                            >
                                <div
                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                    @click="moduleCreateOpen = false"
                                ></div>

                                <div
                                    x-show="moduleCreateOpen"
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                                >
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
                                        action="{{ route('admin.contents.modules.store', $course) }}"
                                        enctype="multipart/form-data"
                                        class="grid gap-4 md:grid-cols-2"
                                    >
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
                                x-show="courseOpen"
                                x-cloak
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="space-y-4"
                            >
                                @forelse ($course->modules as $module)
                                    @php
                                        $moduleHasContext = (int) old('module_context_id') === $module->id_module;
                                        $moduleEditOpen = old('form_scope') === 'module_edit' && $moduleHasContext;
                                        $questionCreateOpen = old('form_scope') === 'question_create' && $moduleHasContext;
                                        $moduleOpen = $moduleHasContext || $moduleEditOpen || $questionCreateOpen;
                                    @endphp

                                    <div
                                        x-data="{
                                            open: @js($moduleOpen),
                                            moduleEditOpen: @js($moduleEditOpen),
                                            questionCreateOpen: @js($questionCreateOpen),
                                        }"
                                        class="surface-muted"
                                    >
                                        <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="flex-1 text-left"
                                            >
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                                        Module #{{ $module->id_module }}
                                                    </span>
                                                    <span class="chip bg-emerald-50 text-emerald-700 border-emerald-100">
                                                        {{ $module->questions->count() }} questions
                                                    </span>
                                                    <span class="chip bg-amber-50 text-amber-700 border-amber-100">
                                                        {{ $module->time_limit }} minutes
                                                    </span>
                                                    @if ($module->material_pdf_path)
                                                        <span class="chip bg-violet-50 text-violet-700 border-violet-100">
                                                            PDF Materi
                                                        </span>
                                                    @endif
                                                </div>
                                                <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ $module->title }}</h4>
                                                <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $module->description }}</p>
                                            </button>

                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                @if ($module->material_pdf_path)
                                                    <a
                                                        href="{{ asset('storage/' . $module->material_pdf_path) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="btn-secondary px-4 py-2 text-xs">
                                                        View PDF
                                                    </a>
                                                @endif
                                                <button
                                                    type="button"
                                                    @click="open = true; questionCreateOpen = true; moduleEditOpen = false"
                                                    class="btn-secondary px-4 py-2 text-xs">
                                                    Add Materi
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="open = true; moduleEditOpen = true; questionCreateOpen = false"
                                                    class="btn-secondary px-4 py-2 text-xs">
                                                    Edit Module
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="openDeleteModal(@js(route('admin.contents.modules.destroy', $module)), @js('module &quot;' . $module->title . '&quot;'))"
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
                                            class="border-t border-slate-200 px-5 py-5 space-y-4"
                                        >
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
                                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                                            >
                                                <div
                                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                    @click="moduleEditOpen = false"
                                                ></div>

                                                <div
                                                    x-show="moduleEditOpen"
                                                    x-transition:enter="ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                    x-transition:leave="ease-in duration-150"
                                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                                                >
                                                    <div class="mb-5 flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="eyebrow">Edit Module</p>
                                                            <h3 class="font-display text-xl text-slate-900">{{ $module->title }}</h3>
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
                                                        action="{{ route('admin.contents.modules.update', $module) }}"
                                                        enctype="multipart/form-data"
                                                        class="grid gap-4 md:grid-cols-2"
                                                    >
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
                                                                value="{{ $moduleEditOpen ? old('title', $module->title) : $module->title }}"
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
                                                            @if ($module->material_pdf_path)
                                                                <a
                                                                    href="{{ asset('storage/' . $module->material_pdf_path) }}"
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
                                                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                                            >
                                                <div
                                                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                    @click="questionCreateOpen = false"
                                                ></div>

                                                <div
                                                    x-show="questionCreateOpen"
                                                    x-transition:enter="ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                    x-transition:leave="ease-in duration-150"
                                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                    class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                                                >
                                                    <div class="mb-5 flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="eyebrow">Add Materi</p>
                                                            <h3 class="font-display text-xl text-slate-900">{{ $module->title }}</h3>
                                                            <p class="mt-1 text-sm text-slate-500">Add a question and its expected output.</p>
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
                                                        class="grid gap-4"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="form_scope" value="question_create">
                                                        <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                        <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">

                                                        <div>
                                                            <label class="form-label">Question</label>
                                                            <textarea
                                                                name="question"
                                                                rows="4"
                                                                class="form-input">{{ $questionCreateOpen ? old('question') : '' }}</textarea>
                                                            @if ($questionCreateOpen)
                                                                @error('question')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                @enderror
                                                            @endif
                                                        </div>

                                                        <div>
                                                            <label class="form-label">Expected Output</label>
                                                            <textarea
                                                                name="output"
                                                                rows="4"
                                                                class="form-input">{{ $questionCreateOpen ? old('output') : '' }}</textarea>
                                                            @if ($questionCreateOpen)
                                                                @error('output')
                                                                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
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
                                                                Save Materi
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                </div>
                                            </template>

                                            <div class="space-y-3">
                                                @forelse ($module->questions as $question)
                                                    @php
                                                        $questionHasContext = (int) old('question_context_id') === $question->id_question;
                                                        $questionEditOpen = old('form_scope') === 'question_edit' && $questionHasContext;
                                                    @endphp

                                                    <div
                                                        x-data="{ questionEditOpen: @js($questionEditOpen) }"
                                                        class="surface-subtle px-4 py-4"
                                                    >
                                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                            <div class="space-y-2 min-w-0">
                                                                <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                                                    Question #{{ $loop->iteration }}
                                                                </span>
                                                                <p class="text-sm font-medium leading-6 text-slate-800">{{ $question->question }}</p>
                                                            </div>

                                                            <div class="w-full space-y-3 lg:max-w-md">
                                                                <div>
                                                                    <p class="eyebrow">Expected Output</p>
                                                                    <p class="mt-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-700 whitespace-pre-wrap">{{ $question->output }}</p>
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
                                                                        @click="openDeleteModal(@js(route('admin.contents.questions.destroy', $question)), @js('question #' . $loop->iteration . ' in &quot;' . $module->title . '&quot;'))"
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
                                                            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:items-center"
                                                        >
                                                            <div
                                                                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                                                                @click="questionEditOpen = false"
                                                            ></div>

                                                            <div
                                                                x-show="questionEditOpen"
                                                                x-transition:enter="ease-out duration-200"
                                                                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                                x-transition:leave="ease-in duration-150"
                                                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                                                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                                                                class="glass relative z-10 max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl"
                                                            >
                                                                <div class="mb-5 flex items-start justify-between gap-4">
                                                                    <div>
                                                                        <p class="eyebrow">Edit Materi</p>
                                                                        <h3 class="font-display text-xl text-slate-900">Question #{{ $loop->iteration }}</h3>
                                                                        <p class="mt-1 text-sm text-slate-500">{{ $module->title }}</p>
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
                                                                    class="grid gap-4"
                                                                >
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="form_scope" value="question_edit">
                                                                    <input type="hidden" name="course_context_id" value="{{ $course->id_course }}">
                                                                    <input type="hidden" name="module_context_id" value="{{ $module->id_module }}">
                                                                    <input type="hidden" name="question_context_id" value="{{ $question->id_question }}">

                                                                    <div>
                                                                        <label class="form-label">Question</label>
                                                                        <textarea
                                                                            name="question"
                                                                            rows="4"
                                                                            class="form-input">{{ $questionEditOpen ? old('question', $question->question) : $question->question }}</textarea>
                                                                        @if ($questionEditOpen)
                                                                            @error('question')
                                                                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                                                                            @enderror
                                                                        @endif
                                                                    </div>

                                                                    <div>
                                                                        <label class="form-label">Expected Output</label>
                                                                        <textarea
                                                                            name="output"
                                                                            rows="4"
                                                                            class="form-input">{{ $questionEditOpen ? old('output', $question->output) : $question->output }}</textarea>
                                                                        @if ($questionEditOpen)
                                                                            @error('output')
                                                                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
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
                                                                            Update Materi
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                @empty
                                                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                                                        No questions have been added to this module yet.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-sm text-slate-500">
                                        No modules are available for this course yet.
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
