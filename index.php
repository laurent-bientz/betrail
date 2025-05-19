<?php

require "vendor/autoload.php";

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$runners = [];
if ('1' === ($_POST['submit'] ?? false)) {
    if (!empty($_POST['data'])) {
        $lines = explode("\n", $_POST['data']);
        foreach ($lines as $line) {
            if (str_contains($line, "\t") && !empty($infos = explode("\t", $line)) && 1 < \count($infos)) {
                $line = $infos[0] . ' ' . $infos[1];
            }
            try {
                $response = $client->request('GET', sprintf('https://www.betrail.run/api/runner/search?queryString=%s', $line));
                $runner = $response->toArray()['body'][0]['close_runners'][0]['_source'] ?? null;
                if (null === $runner) {
                    throw new \Exception('Runner not found');
                }
                $title = explode(' ', $runner['clean_display_title']);
                $runners[] = [
                    'name' => strtoupper(($runner['lastname'] ?? $runner['clean_display_title']) . ' ' . ($runner['firstname'] ?? '')),
                    'ranking' => $runner['bt_level'],
                    'slug' => $runner['alias'],
                    'gender' => 1 === ($runner['gender'] ?? 0) ? '♀️' : '♂️',
                ];
            }
            catch (\Exception $e) {
                $line = preg_replace('/\s/', ' ', $line);
                $runners[] = [
                    'name' => strtoupper($line),
                    'ranking' => null,
                ];
            }
        }
    }
}
usort($runners, fn ($a, $b) => $a['ranking'] === $b['ranking'] ? 0 : (($a['ranking'] > $b['ranking']) ? -1 : 1));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Betrail Ranking</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
</head>

<body>

    <div class="container">
        <h1>Betrail Ranking</h1>
        <hr />

        <div class="row">
            <form method="POST" action="">
                <input type="hidden" name="submit" value="1" />
                <div class="form-group">
                    <label for="data">List of runners</label>
                    <textarea class="form-control" name="data" id="data" rows="20" placeholder="One per line"><?= $_POST['data'] ?? ''?></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>

        <?php if (!empty($runners)): ?>
            <div class="row">
                <div class="table-responsive">
                    <table class="table table-hover table-striped caption-top">
                        <thead class="table-light">
                        <tr>
                            <th class="text-center" scope="col">Name</th>
                            <th class="text-center" scope="col">Gender</th>
                            <th class="text-center" scope="col">Ranking</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($runners as $data): ?>
                            <tr>
                                <td class="text-center"><?= null !== ($data['slug'] ?? null) ? '<a href="https://www.betrail.run/runner/' . $data['slug'] . '" target="_blank">' . $data['name'] . '</a>' : $data['name'] ?></td>
                                <td class="text-center"><?= $data['gender'] ?? '-' ?></td>
                                <td class="text-center"><?= (!empty($data['ranking'])) ? number_format($data['ranking'] / 100, 2, '.') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
