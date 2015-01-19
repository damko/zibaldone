<?php

class Downloader {

    public static function downloadFromGithub(GithubReference $github_ref)
    {
        $client = new \Github\Client();

        try {
            $fileInfo = $client
                                ->api('repo')->contents()
                                ->show($github_ref->repo_user, $github_ref->repo_name, $github_ref->repo_path, $github_ref->sha);
        } catch (Exception $e) {
            return false;
        }

        if (isset($fileInfo[0])) {
            // the user provided the path of a directory instead of a file
            return false;
        }

        if ($download = Download::where('reference_id', '=', $github_ref->reference_id)->first()) {

            //update
            $download->full_filename = $fileInfo['name'];
            $download->sha = $fileInfo['sha'];
            $download->content = $fileInfo['content'];
            $download->base64_md5 = md5($fileInfo['content']);
            $download->size = $fileInfo['size'];
            $download->html_url = $fileInfo['html_url'];

        } else {

            //create
            $download = new Download();
            $download->reference_id = $github_ref->reference_id;
            $download->full_filename = $fileInfo['name'];
            $download->sha = $fileInfo['sha'];
            $download->content = $fileInfo['content'];
            $download->base64_md5 = md5($fileInfo['content']);
            $download->size = $fileInfo['size'];
            $download->html_url = $fileInfo['html_url'];
        }

        if ($download->save()) {
            return $download;
        }

        return false;
    }

}
