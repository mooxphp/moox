<form method="POST" action="{{ route('login-link.send') }}">
    @csrf
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <button type="submit">Send Login Link</button>
</form>
