<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Skillbytes API",
 *     description="Complete REST API for Skillbytes Learning Management System - includes PBL, Assessments, Tracking, and SPK modules",
 *     @OA\Contact(
 *         name="Skillbytes Support",
 *         email="support@skillbytes.com"
 *     ),
 *     @OA\License(
 *         name="MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development"
 * )
 *
 * @OA\Server(
 *     url="http://145.79.13.180/api",
 *     description="Production Server"
 * )
 *
 * @OA\Components(
 *     @OA\SecurityScheme(
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="Bearer Token (Sanctum)",
 *         securityScheme="bearerAuth",
 *         description="Bearer token for API authentication using Laravel Sanctum"
 *     ),
 *
 *     @OA\Schema(
 *         schema="User",
 *         type="object",
 *         description="User resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *         @OA\Property(property="role", type="string", enum={"admin", "guru", "siswa"}, example="siswa"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="MataPelajaran",
 *         type="object",
 *         description="Subject (Mata Pelajaran) resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Matematika"),
 *         @OA\Property(property="description", type="string", example="Pelajaran Matematika"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="Kelas",
 *         type="object",
 *         description="Kelas resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nama", type="string", example="Kelas 10 A"),
 *         @OA\Property(property="guru_id", type="integer", example=2),
 *         @OA\Property(property="guru", type="object", ref="#/components/schemas/User", nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="Lesson",
 *         type="object",
 *         description="Lesson resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="mata_pelajaran_id", type="integer", example=1),
 *         @OA\Property(property="kelas_id", type="integer", example=1, nullable=true),
 *         @OA\Property(property="title", type="string", example="Introduction to Functions"),
 *         @OA\Property(property="slug", type="string", example="introduction-to-functions"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="resume", type="string", nullable=true),
 *         @OA\Property(property="file_path", type="string", nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="UserResume",
 *         type="object",
 *         description="User Resume resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="user_id", type="integer", example=1),
 *         @OA\Property(property="lesson_id", type="integer", example=1),
 *         @OA\Property(property="content", type="string"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="PblCase",
 *         type="object",
 *         description="PBL Case resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="mata_pelajaran_id", type="integer", example=1),
 *         @OA\Property(property="kelas_id", type="integer", example=1, nullable=true),
 *         @OA\Property(property="title", type="string", example="System Login Authentication Failed"),
 *         @OA\Property(property="slug", type="string", example="system-login-authentication-failed"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="image_url", type="string", format="uri", nullable=true),
 *         @OA\Property(property="start_date", type="string", format="date-time"),
 *         @OA\Property(property="deadline", type="string", format="date-time"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="CaseSection",
 *         type="object",
 *         description="PBL Case Section resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="pbl_case_id", type="integer", example=1),
 *         @OA\Property(property="title", type="string", example="Problem Statement"),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="CaseSectionItem",
 *         type="object",
 *         description="PBL Case Section Item resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="case_section_id", type="integer", example=1),
 *         @OA\Property(property="content", type="string"),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="CaseSubmission",
 *         type="object",
 *         description="PBL Case Submission resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="pbl_case_id", type="integer", example=1),
 *         @OA\Property(property="user_id", type="integer", example=1),
 *         @OA\Property(property="answer", type="string", nullable=true),
 *         @OA\Property(property="submission_file", type="string", nullable=true, format="uri"),
 *         @OA\Property(property="submitted_at", type="string", format="date-time"),
 *         @OA\Property(property="score", type="number", format="float", nullable=true),
 *         @OA\Property(property="feedback", type="string", nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="Assessment",
 *         type="object",
 *         description="Assessment (Quiz/Exam) resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="mata_pelajaran_id", type="integer", example=1),
 *         @OA\Property(property="kelas_id", type="integer", example=1, nullable=true),
 *         @OA\Property(property="title", type="string", example="Chapter 1 Quiz"),
 *         @OA\Property(property="slug", type="string", example="chapter-1-quiz"),
 *         @OA\Property(property="description", type="string"),
 *         @OA\Property(property="duration", type="integer", example=60, description="Duration in minutes"),
 *         @OA\Property(property="passing_score", type="integer", example=70),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="Question",
 *         type="object",
 *         description="Assessment Question resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="assessment_id", type="integer", example=1),
 *         @OA\Property(property="question_text", type="string"),
 *         @OA\Property(property="question_type", type="string", enum={"multiple_choice", "essay"}, example="multiple_choice"),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="Option",
 *         type="object",
 *         description="Question Option resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="question_id", type="integer", example=1),
 *         @OA\Property(property="option_text", type="string", example="Option A"),
 *         @OA\Property(property="is_correct", type="boolean", example=true),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="AssessmentAttempt",
 *         type="object",
 *         description="Assessment Attempt resource",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="user_id", type="integer", example=1),
 *         @OA\Property(property="assessment_id", type="integer", example=1),
 *         @OA\Property(property="started_at", type="string", format="date-time"),
 *         @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
 *         @OA\Property(property="score", type="number", format="float", nullable=true),
 *         @OA\Property(property="status", type="string", enum={"started", "finished"}, example="started"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *
 *     @OA\Schema(
 *         schema="AuthToken",
 *         type="object",
 *         description="Authentication token response",
 *         @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz123456"),
 *         @OA\Property(property="user", ref="#/components/schemas/User")
 *     ),
 *
 *     @OA\Schema(
 *         schema="ErrorResponse",
 *         type="object",
 *         description="Error response",
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="message", type="string", example="Error message"),
 *         @OA\Property(property="errors", type="object", nullable=true)
 *     ),
 *
 *     @OA\Schema(
 *         schema="SuccessResponse",
 *         type="object",
 *         description="Generic success response",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="Success message"),
 *         @OA\Property(property="data", type="object", nullable=true)
 *     )
 * )
 */

class SwaggerController
{
    // This file is only for Swagger documentation
}

