<?php
require_once relative_path("includes/components.php");

$title = 'Academic Term / Session';
$page_title = "Academic Sessions";

// Pre-calculate URL for JS
$ajax_url = url('admin/ajax/session.php');

ob_start();
?>

<div x-data="sessionManager()" x-init="fetchSessions()" class="container px-6 mx-auto grid">
    <?= information_bar("When you set an academic session, the system will automatically determine the current term (semester) based on its start and end dates.", "info", false, ["class" => "mb-6"]) ?>

    <div class="flex flex-col md:flex-row justify-between items-center my-6 gap-4">
        <?= button("button", "Create New Session", attributes: ["@click" => "openModal()", "class" => "flex items-center gap-2"]) ?>
    </div>

    <div x-show="isLoading" class="text-center py-10" style="display: none;">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Loading sessions...</p>
    </div>

    <div x-show="!isLoading && sessions.length === 0" class="mb-8" style="display: none;">
        <?= placeholder_element("No Academic Sessions Found", "Get started by creating a new academic session.", "fas fa-calendar-times") ?>
    </div>

    <div x-show="!isLoading && sessions.length > 0" class="grid gap-6 mb-8 lg:grid-cols-1" style="display: none;">
        <template x-for="session in sessions" :key="session.id">
            <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800 border-l-4 transition hover:shadow-md" :class="session.is_current == 1 ? 'border-green-500' : 'border-gray-200 dark:border-gray-700'">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h4 class="text-lg font-semibold text-gray-600 dark:text-gray-300" x-text="session.name"></h4>
                            <span x-show="session.is_current == 1" class="px-2 py-1 text-xs font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                Current Session
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <i class="far fa-calendar-alt mr-1"></i>
                            <span x-text="formatDate(session.start_date)"></span> &mdash; <span x-text="formatDate(session.end_date)"></span>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="editSession(session)" class="px-3 py-1 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-md active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple" aria-label="Edit">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button @click="deleteSession(session.id)" class="px-3 py-1 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-red-600 border border-transparent rounded-md active:bg-red-600 hover:bg-red-700 focus:outline-none focus:shadow-outline-red" aria-label="Delete">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t dark:border-gray-700">
                    <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Semesters / Terms</h5>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <template x-for="semester in session.semesters" :key="semester.id">
                            <div class="p-3 rounded-md border bg-gray-50 dark:bg-gray-700 dark:border-gray-600 relative" :class="semester.is_active == 1 ? 'border-green-300 dark:border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200'">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-700 dark:text-gray-200 text-sm" x-text="semester.name"></span>
                                    <span x-show="semester.is_active == 1" class="h-2.5 w-2.5 rounded-full bg-green-500 shadow-sm" title="Active Term"></span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                    <i class="far fa-clock mr-1.5 opacity-70"></i>
                                    <span x-text="formatDate(semester.start_date)"></span> 
                                    <span class="mx-1">-</span> 
                                    <span x-text="formatDate(semester.end_date)"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="!session.semesters || session.semesters.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic p-2">
                            No semesters defined.
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <?= modal_start(["@click.away" => "closeModal()", "@keydown.escape" => "closeModal()"]) ?>
        <form @submit.prevent="saveSession" id="session-form">
            <div class="flex justify-between items-center mb-4">
                <?= modal_title("", attributes: ["x-text" => "isEditing ? 'Edit Academic Session' : 'New Academic Session'"]) ?>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <?= modal_body_start() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-1 md:col-span-2">
                        <?= input("text", "Session Name", "name", "", false, ["x-model" => "form.name", "placeholder" => "e.g. 2025/2026", "required" => "true"]) ?>
                    </div>
                    <div>
                        <?= input("date", "Start Date", "start_date", "", false, ["x-model" => "form.start_date", "required" => "true"]) ?>
                    </div>
                    <div>
                        <?= input("date", "End Date", "end_date", "", false, ["x-model" => "form.end_date", "required" => "true"]) ?>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <?= checkbox("is_current", "1", "Set as Current Academic Session", false, ["x-model" => "form.is_current"]) ?>
                    </div>
                </div>

                <div class="mt-6 border-t dark:border-gray-700 pt-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Semesters / Terms</h4>
                        <button type="button" @click="addSemester()" class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-2 py-1 rounded dark:bg-purple-700 dark:text-purple-100 dark:hover:bg-purple-600 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Add Semester
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <template x-for="(semester, index) in form.semesters" :key="index">
                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md border border-gray-200 dark:border-gray-600 relative group">
                                <button type="button" @click="removeSemester(index)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-6">
                                    <div class="col-span-1 md:col-span-2">
                                        <?= input("text", "", "", "", false, ["x-model" => "semester.name", "placeholder" => "Semester Name", "readonly" => "true", "class" => "bg-gray-100 cursor-not-allowed"]) ?>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Start</span>
                                        <?= input("date", "", "", "", false, ["x-model" => "semester.start_date", "required" => "true"]) ?>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">End</span>
                                        <?= input("date", "", "", "", false, ["x-model" => "semester.end_date", "required" => "true"]) ?>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="form.semesters.length === 0" class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm italic">
                            No semesters added. Click "Add Semester" to define semesters.
                        </div>
                    </div>
                </div>
            <?= modal_body_end() ?>

            <?= modal_footer_start() ?>
                <?= modal_reset_btn("Cancel") ?>
                <button type="submit" class="w-full px-5 py-3 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg sm:w-auto sm:px-4 sm:py-2 active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple" :disabled="isSaving">
                    <span x-show="!isSaving">Save Session</span>
                    <span x-show="isSaving"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                </button>
            <?= modal_footer_end() ?>
        </form>
    <?= modal_end() ?>
</div>

<?php $scripts = <<<HTML
<script>
    const ajax_url = "$ajax_url";
    function sessionManager() {
        return {
            sessions: [],
            isLoading: true,
            isModalOpen: false,
            isEditing: false,
            isSaving: false,
            form: {
                id: null,
                name: '',
                start_date: '',
                end_date: '',
                is_current: false,
                semesters: [],
                deleted_semesters: []
            },
            
            fetchSessions() {
                this.isLoading = true;
                var self = this;
                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    data: {
                        submit: 'fetch_sessions',
                        response_type: 'json'
                    },
                    success: function(response) {
                        self.isLoading = false;
                        if(response.status) {
                            self.sessions = response.data.sessions || [];
                        } else {
                            alert_box('Failed to fetch sessions', 'danger');
                        }
                    },
                    error: function(err) {
                        self.isLoading = false;
                        alert_box('System error occurred', 'danger');
                    }
                });
            },
            
            openModal() {
                this.resetForm();
                this.isModalOpen = true;
            },
            
            closeModal() {
                this.isModalOpen = false;
                this.resetForm();
            },
            
            resetForm() {
                this.isEditing = false;
                this.form = {
                    id: null,
                    name: '',
                    start_date: '',
                    end_date: '',
                    is_current: false,
                    semesters: [],
                    deleted_semesters: []
                };
                
                setTimeout(function() {
                    $('#session-form input').removeClass("border-red-600 dark:border-red-400");
                    $('#session-form .error-span').remove();
                }, 100);
            },
            
            addSemester() {
                if(this.form.semesters.length >= 3) {
                    alert_box("Maximum of 3 semesters allowed", "warning");
                    return;
                }
                var count = this.form.semesters.length + 1;
                var nextName = 'Semester ' + count;
                
                this.form.semesters.push({
                    id: null,
                    name: nextName,
                    start_date: '',
                    end_date: '',
                });
            },
            
            removeSemester(index) {
                var semester = this.form.semesters[index];
                if(semester.id) {
                    this.form.deleted_semesters.push(semester.id);
                }
                this.form.semesters.splice(index, 1);
                
                this.form.semesters.forEach(function(sem, idx) {
                    if(!sem.id) {
                        sem.name = 'Semester ' + (idx + 1);
                    }
                });
            },
            
            editSession(session) {
                this.isEditing = true;
                var sessionCopy = JSON.parse(JSON.stringify(session));
                
                this.form = {
                    id: sessionCopy.id,
                    name: sessionCopy.name || '',
                    start_date: sessionCopy.start_date || '',
                    end_date: sessionCopy.end_date || '',
                    is_current: sessionCopy.is_current == 1,
                    semesters: (sessionCopy.semesters || []).map(function(s) {
                        return {
                            id: s.id,
                            name: s.name || '',
                            start_date: s.start_date || '',
                            end_date: s.end_date || '',
                        };
                    }),
                    deleted_semesters: []
                };
                
                this.isModalOpen = true;
            },
            
            saveSession() {
                this.isSaving = true;
                var action = this.isEditing ? 'update_session' : 'add_session';
                var self = this;
                
                var formData = {
                    id: this.form.id,
                    name: this.form.name,
                    start_date: this.form.start_date,
                    end_date: this.form.end_date,
                    is_current: this.form.is_current ? 1 : 0,
                    semesters: this.form.semesters,
                    deleted_semesters: this.form.deleted_semesters,
                    submit: action,
                    response_type: 'json'
                };
                
                $.ajax({
                    url: '$ajax_url',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        self.isSaving = false;
                        if(response.status) {
                            alert_box(response.data.message || 'Operation successful', 'success');
                            self.fetchSessions();
                            self.closeModal();
                        } else {
                            if(response.errors) {
                                display_form_errors(response.errors, $('#session-form'));
                            } else {
                                alert_box('An error occurred', 'danger');
                            }
                        }
                    },
                    error: function(err) {
                        self.isSaving = false;
                        alert_box('System error occurred', 'danger');
                    }
                });
            },
            
            deleteSession(id) {
                if(!confirm('Are you sure you want to delete this session? All associated data will be removed.')) return;
                
                var self = this;
                $.ajax({
                    url: '$ajax_url',
                    method: 'POST',
                    data: {
                        id: id,
                        submit: 'delete_session',
                        response_type: 'json'
                    },
                    success: function(response) {
                        if(response.status) {
                            alert_box(response.data.message || 'Session deleted successfully', 'success');
                            self.fetchSessions();
                        } else {
                            alert_box(response.errors ? response.errors.system_error : 'Failed to delete session', 'danger');
                        }
                    },
                    error: function(err) {
                        alert_box('System error occurred', 'danger');
                    }
                });
            },
            
            formatDate(dateString) {
                if(!dateString) return '';
                try {
                    var date = new Date(dateString);
                    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) {
                    return dateString;
                }
            }
        }
    }
</script>
HTML;
?>

<?php
$content = ob_get_clean();
require relative_path('layouts/auth.php');
?>