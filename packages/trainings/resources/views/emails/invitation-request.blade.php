<!DOCTYPE html>
<html>
<head>
    <title>Invitation Request</title>
</head>
<body>
    <h1>Invitation Request for {{ $invitationRequest->title }}</h1>
    <p>Invitation request has been received for the training with ID: {{ $invitationRequest->id }}</p>
    <p>Click <a href="https://moox-press.test/admin/training-invitations/{{ $invitationRequest->id }}/prepare">here</a> to prepare the training.</p>
</body>
</html>
