    <script>
        const data = {
            action: "frame",
            id: {{$item->id}},
            name: "{{$item->name}}",
        }
        // Option 2: Use postMessage for safer cross-origin communication
        window.opener.postMessage(data, window.location.origin);

        // Close the child window
        window.close();
    </script>
