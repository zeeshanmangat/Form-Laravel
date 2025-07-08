@extends('layouts.app')
@section('title', 'AJAX User CRUD')
@section('content')
<div class="container my-4">
    <div class="row g-4 justify-content-center">
        <!-- Form -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 id="form-title" class="mb-0">Add New User</h5>
                </div>
                <div class="card-body">
                    <form id="userForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="userId" name="userId">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" id="image">
                            <div id="preview" class="mt-2"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100" id="submitBtn">Save User</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Table -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">User List</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTable"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    fetchUsers();

    $('#userForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const id = $('#userId').val();
        const url = id ? `/users/${id}` : "{{ route('users.store') }}";

        // 🟡 Use _method=PUT for Laravel update logic
        if (id) {
            formData.append('_method', 'PUT');
        }

        // Remove image field if no file selected to avoid 422 error
        if (!$('#image')[0].files.length) {
            formData.delete('image');
        }

        $.ajax({
            url: url,
            method: 'POST', // always POST when using FormData
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#userForm')[0].reset();
                $('#userId').val('');
                $('#submitBtn').text('Save User');
                $('#form-title').text('Add New User');
                $('#preview').html('');
                fetchUsers();
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Validation errors:\n';
                    for (const field in errors) {
                        errorMessage += `${field}: ${errors[field].join(', ')}\n`;
                    }
                    alert(errorMessage);
                } else {
                    alert('Something went wrong! ' + xhr.statusText);
                }
            }
        });
    });

    // Fetch and populate user list
    function fetchUsers() {
        $.get("{{ route('users.index') }}", function(data) {
            $('#userTable').html('');
            data.forEach(user => {
                $('#userTable').append(`
                    <tr>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.image ? `<img src="/storage/${user.image}" width="50" class="rounded-circle">` : 'No Image'}</td>
                        <td>
                            <button class="btn btn-sm btn-warning me-1" onclick="editUser(${user.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>
                        </td>
                    </tr>
                `);
            });
        });
    }

    // Make fetchUsers globally available
    window.fetchUsers = fetchUsers;
});

// Populate form for editing a user
function editUser(id) {
    $.get(`/users/${id}`, function(user) {
        $('#userId').val(user.id);
        $('#name').val(user.name);
        $('#email').val(user.email);
        $('#password').val(''); // Clear password for security
        $('#submitBtn').text('Update User');
        $('#form-title').text('Edit User');

        if (user.image) {
            $('#preview').html(`<img src="/storage/${user.image}" width="100" class="rounded">`);
        } else {
            $('#preview').html('');
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }).fail(function(xhr) {
        alert('Error fetching user: ' + xhr.statusText);
    });
}

// Delete a user
function deleteUser(id) {
    if (confirm('Are you sure?')) {
        $.ajax({
            url: `/users/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                fetchUsers();
            },
            error: function(xhr) {
                alert('Error deleting user: ' + xhr.statusText);
            }
        });
    }
}
</script>

@endsection
