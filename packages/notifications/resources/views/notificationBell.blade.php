<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bell Api</title>

    <style>
            @keyframes ringAnimation {
        0% { transform: rotate(0); }
        10% { transform: rotate(30deg); }
        20% { transform: rotate(-28deg); }
        30% { transform: rotate(34deg); }
        40% { transform: rotate(-32deg); }
        50% { transform: rotate(30deg); }
        60% { transform: rotate(-28deg); }
        70% { transform: rotate(32deg); }
        80% { transform: rotate(-30deg); }
        90% { transform: rotate(28deg); }
        100% { transform: rotate(0); }
    }
    .bell-icon {
    animation: ringAnimation 1s ease-in-out 1s, colorChange 2s ease-in-out 1s;
    position: relative;
    }

    .gray{
        color:gray;
    }
        @keyframes colorChange {
            0%, 100% { fill: transparent; }   /* Original color at the start and end */
            /* 50% { fill: rgb(216, 132, 36); }          Amber color in the middle of the animation */
            50% { fill: #005d9d; }          Amber color in the middle of the animation
        }
    </style>

</head>
<body>
    @if ($unreadNotificationsCount != 0)
    <div class="notification">
      <a href="/admin" style="display:flex;">
            <div style="display:flex;" >
                <x-heroicon-o-bell class="bell-icon" style="width:25px;" />
                <div class="countBadge" style="font-family:Arial; display:inline-block; position:relative; top:-8px;">{{$unreadNotificationsCount}}</div>
            </div>
        </a>
    </div>
    @else
    <div class="gray">
        <a href="/admin" style="display:flex;" class="gray">
                <div style="display:flex;" >
                    <x-heroicon-o-bell class="gray" style="width:25px;" />
                </div>
          </a>
      </div>
    @endif



</body>
</html>

