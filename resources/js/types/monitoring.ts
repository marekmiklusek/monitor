export type HeartbeatStatus = 'ok' | 'stale' | 'missing';

export type IssueStatus = 'open' | 'resolved' | 'ignored';

export type OccurrenceType =
    | 'exception'
    | 'failed_job'
    | 'slow_query'
    | 'heartbeat'
    | 'log';

export interface ProjectSummary {
    id: string;
    name: string;
    environment: string;
}

export interface DashboardProject extends ProjectSummary {
    open_issues_count: number;
    recent_occurrences_count: number;
    heartbeat_status: HeartbeatStatus;
    last_heartbeat_at: string | null;
}

export interface ProjectListItem extends ProjectSummary {
    heartbeat_status: HeartbeatStatus;
    last_heartbeat_at: string | null;
    created_at: string;
}

export interface IssueRow {
    id: string;
    type: OccurrenceType;
    title: string;
    message: string | null;
    status: IssueStatus;
    occurrences_count: number;
    last_seen_at: string;
    project: ProjectSummary;
}

export interface IssueDetail {
    id: string;
    type: OccurrenceType;
    title: string;
    message: string | null;
    file: string | null;
    line: number | null;
    status: IssueStatus;
    occurrences_count: number;
    first_seen_at: string;
    last_seen_at: string;
    project: ProjectSummary;
}

export interface OccurrenceSummary {
    id: string;
    occurred_at: string;
}

export interface OccurrenceDetail extends OccurrenceSummary {
    payload: OccurrencePayload;
}

export interface OccurrencePayload {
    type?: OccurrenceType;
    exception_class?: string | null;
    message?: string | null;
    file?: string | null;
    line?: number | null;
    channel?: string | null;
    stack?: unknown;
    context?: Record<string, unknown> | null;
    breadcrumbs?: Breadcrumb[] | null;
    [key: string]: unknown;
}

export interface Breadcrumb {
    level: string;
    message: string;
    context?: Record<string, unknown> | null;
    logged_at: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface IssueFilters {
    status: IssueStatus | 'all';
    project: string | null;
}
