<!DOCTYPE html>
<html>
<head>
    <title>Invitation</title>
</head>
<body>
    <h1>Invitation for ...</h1>

    @foreach ($trainingDates as $trainingDate)
        <p>Datum: {{ $trainingDate->date }}</p>
    @endforeach

</body>
</html>
